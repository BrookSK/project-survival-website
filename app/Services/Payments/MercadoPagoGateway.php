<?php

namespace App\Services\Payments;

use App\Core\Logger;
use App\Services\GameApi\HttpClient;
use App\Services\GameApi\HttpResponse;

/**
 * Gateway do Mercado Pago (server-side).
 *
 * Suporta PIX (QR dinâmico) e cartão (via token gerado no navegador — o site
 * NUNCA recebe/armazena PAN). Consulta de pagamento, estorno e validação de
 * assinatura de webhook.
 *
 * Segurança:
 *   - Access Token nunca sai em log/resposta (HttpClient já sanitiza headers).
 *   - Webhook validado por HMAC-SHA256 (header `x-signature`: ts + v1) sobre o
 *     template oficial `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`.
 *   - O payload do webhook NUNCA é prova; o status real vem de getPayment().
 *
 * Nada aqui simula pagamento: sem Access Token válido/aprovação do MP, o
 * estado não avança.
 */
class MercadoPagoGateway implements PaymentGatewayInterface
{
    private const API_BASE = 'https://api.mercadopago.com';

    public function __construct(
        private string $accessToken,
        private string $webhookSecret,
        private string $currency = 'BRL',
        private ?HttpClient $http = null
    ) {
        $this->http = $http ?? new HttpClient(12, 5);
    }

    public function name(): string
    {
        return 'mercadopago';
    }

    public function isEnabled(): bool
    {
        return $this->accessToken !== '';
    }

    public function createPayment(array $order, string $method, array $options = []): PaymentIntent
    {
        $this->assertConfigured();

        $amountCents = (int) ($order['total_cents'] ?? 0);
        if ($amountCents <= 0) {
            throw PaymentException::permanent('Valor do pedido inválido.');
        }
        $amount = round($amountCents / 100, 2);
        $reference = (string) ($order['reference'] ?? '');
        $email = (string) ($order['player_email'] ?? '');

        $payload = [
            'transaction_amount' => $amount,
            'description'        => 'Pedido ' . $reference,
            'external_reference' => $reference,
            // notification_url é configurada no painel do MP; mantemos external_reference.
        ];

        if ($method === 'pix') {
            $payload['payment_method_id'] = 'pix';
            $payload['payer'] = ['email' => $email !== '' ? $email : 'comprador@example.com'];
        } elseif ($method === 'credit_card') {
            $token = (string) ($options['card_token'] ?? '');
            if ($token === '') {
                throw PaymentException::permanent('Token do cartão ausente.');
            }
            $payload['token'] = $token;
            $payload['installments'] = max(1, (int) ($options['installments'] ?? 1));
            if (!empty($options['payment_method_id'])) {
                $payload['payment_method_id'] = (string) $options['payment_method_id'];
            }
            $payload['payer'] = ['email' => $email !== '' ? $email : 'comprador@example.com'];
        } else {
            throw PaymentException::permanent('Método de pagamento não suportado: ' . $method);
        }

        // Idempotency-Key evita cobrança dupla em reenvios (duplo clique/retry).
        $idempotencyKey = 'order-' . $reference;
        $response = $this->request('POST', '/v1/payments', $payload, [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $data = $this->decodeOrFail($response);
        return $this->toIntent($data, $method);
    }

    public function getPayment(string $externalId): PaymentIntent
    {
        $this->assertConfigured();
        $response = $this->request('GET', '/v1/payments/' . rawurlencode($externalId));
        $data = $this->decodeOrFail($response);
        return $this->toIntent($data, (string) ($data['payment_type_id'] ?? ''));
    }

    public function refundPayment(string $externalId, ?int $amountCents = null): RefundResult
    {
        $this->assertConfigured();
        $body = null;
        if ($amountCents !== null && $amountCents > 0) {
            $body = ['amount' => round($amountCents / 100, 2)];
        }
        // Idempotency-Key evita estorno duplicado.
        $response = $this->request('POST', '/v1/payments/' . rawurlencode($externalId) . '/refunds', $body, [
            'X-Idempotency-Key' => 'refund-' . $externalId . '-' . ($amountCents ?? 'full'),
        ]);
        $data = $this->decodeOrFail($response);
        $refunded = isset($data['amount']) ? (int) round(((float) $data['amount']) * 100) : ($amountCents ?? 0);
        return new RefundResult(
            externalId: (string) ($data['id'] ?? ''),
            status: (string) ($data['status'] ?? 'refunded'),
            refundedCents: $refunded,
            raw: $this->sanitize($data)
        );
    }

    public function parseWebhook(array $headers, string $rawBody, array $query = []): WebhookResult
    {
        $lower = [];
        foreach ($headers as $k => $v) {
            $lower[strtolower((string) $k)] = is_array($v) ? (string) reset($v) : (string) $v;
        }

        $body = json_decode($rawBody, true);
        $body = is_array($body) ? $body : [];

        // ID do pagamento referenciado: pode vir em data.id (body) ou data.id (query).
        $dataId = (string) ($body['data']['id'] ?? $query['data.id'] ?? $query['id'] ?? '');
        $eventType = (string) ($body['type'] ?? $body['action'] ?? $query['type'] ?? '');
        // event id para idempotência: usa o id do webhook do MP quando presente.
        $eventId = (string) ($body['id'] ?? $lower['x-request-id'] ?? '');

        $signatureValid = $this->verifySignature($lower, $dataId, $query);

        return new WebhookResult(
            signatureValid: $signatureValid,
            eventId: $eventId !== '' ? $eventId : null,
            eventType: $eventType !== '' ? $eventType : null,
            externalPaymentId: $dataId !== '' ? $dataId : null,
            raw: $this->sanitize($body)
        );
    }

    /**
     * Valida a assinatura HMAC-SHA256 do webhook (header x-signature: ts=..,v1=..).
     * Template oficial: id:<data.id>;request-id:<x-request-id>;ts:<ts>;
     */
    private function verifySignature(array $lowerHeaders, string $dataId, array $query): bool
    {
        if ($this->webhookSecret === '') {
            // Sem segredo configurado não há como validar -> rejeita (nunca "confia").
            return false;
        }
        $signature = $lowerHeaders['x-signature'] ?? '';
        $requestId = $lowerHeaders['x-request-id'] ?? '';
        if ($signature === '') {
            return false;
        }

        $ts = '';
        $hash = '';
        foreach (explode(',', $signature) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) {
                continue;
            }
            [$k, $v] = $kv;
            $k = trim($k);
            $v = trim($v);
            if ($k === 'ts') {
                $ts = $v;
            } elseif ($k === 'v1') {
                $hash = $v;
            }
        }
        if ($ts === '' || $hash === '') {
            return false;
        }

        // data.id em minúsculas conforme documentação do MP.
        $id = strtolower($dataId);
        $manifest = "id:{$id};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($expected, $hash);
    }

    /**
     * Normaliza a resposta do MP em um PaymentIntent.
     */
    private function toIntent(array $data, string $method): PaymentIntent
    {
        $status = $this->mapStatus((string) ($data['status'] ?? ''));
        $amountCents = isset($data['transaction_amount'])
            ? (int) round(((float) $data['transaction_amount']) * 100)
            : 0;

        $poi = $data['point_of_interaction']['transaction_data'] ?? [];
        $pixCode = $poi['qr_code'] ?? null;
        $pixBase64 = $poi['qr_code_base64'] ?? null;
        // Link de checkout hospedado, quando aplicável.
        $checkoutUrl = $data['point_of_interaction']['transaction_data']['ticket_url'] ?? null;

        return new PaymentIntent(
            externalId: (string) ($data['id'] ?? ''),
            status: $status,
            method: $method !== '' ? $method : (string) ($data['payment_type_id'] ?? ''),
            amountCents: $amountCents,
            currency: (string) ($data['currency_id'] ?? $this->currency),
            checkoutUrl: $checkoutUrl,
            pixQrCode: $pixCode,
            pixQrBase64: $pixBase64,
            raw: $this->sanitize($data)
        );
    }

    /**
     * Mapeia status do Mercado Pago para o vocabulário normalizado.
     */
    private function mapStatus(string $mpStatus): string
    {
        return match (strtolower($mpStatus)) {
            'approved', 'authorized'      => PaymentStatus::APPROVED,
            'in_process', 'pending'       => PaymentStatus::PENDING,
            'in_mediation'                => PaymentStatus::IN_PROCESS,
            'rejected'                    => PaymentStatus::REJECTED,
            'cancelled'                   => PaymentStatus::CANCELLED,
            'refunded'                    => PaymentStatus::REFUNDED,
            'charged_back'                => PaymentStatus::CHARGED_BACK,
            default                       => PaymentStatus::PENDING,
        };
    }

    private function request(string $method, string $path, ?array $body = null, array $extraHeaders = []): HttpResponse
    {
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->accessToken,
        ], $extraHeaders);

        $response = $this->http->request($method, self::API_BASE . $path, $headers, $body);

        if ($response->isTransportError()) {
            Logger::warning('mercadopago.transport_error', ['path' => $path, 'error' => $response->transportError]);
            throw PaymentException::transient('Falha de comunicação com o gateway de pagamento.');
        }

        $status = $response->status;
        if ($status >= 500 || $status === 429) {
            throw PaymentException::transient('Gateway de pagamento indisponível.', $status);
        }
        if ($status === 401 || $status === 403) {
            throw PaymentException::permanent('Credenciais do gateway inválidas.', $status);
        }
        if ($status >= 400) {
            $msg = 'Requisição rejeitada pelo gateway.';
            $json = $response->json();
            if (isset($json['message']) && is_string($json['message'])) {
                $msg = 'Gateway: ' . $json['message'];
            }
            throw PaymentException::permanent($msg, $status);
        }

        return $response;
    }

    private function decodeOrFail(HttpResponse $response): array
    {
        $data = $response->json();
        if ($data === null) {
            throw PaymentException::transient('Resposta inválida do gateway de pagamento.', $response->status);
        }
        return $data;
    }

    private function assertConfigured(): void
    {
        if ($this->accessToken === '') {
            throw PaymentException::permanent('Access Token do Mercado Pago não configurado.');
        }
    }

    /**
     * Remove campos sensíveis do snapshot antes de persistir/logar.
     */
    private function sanitize(array $data): array
    {
        unset($data['token'], $data['card'], $data['payer']['identification']);
        return $data;
    }
}
