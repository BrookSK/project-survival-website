<?php

namespace App\Services\Commerce;

use App\Core\Logger;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\HttpClient;
use App\Services\GameApi\UrlGuard;
use App\Services\Payments\PaymentConfig;

/**
 * Adapter da concessão de itens na Game API (ÚNICA autoridade de entitlement).
 *
 * REGRAS INEGOCIÁVEIS:
 *   - NUNCA insere entitlement/inventário no banco do site. Apenas registra a
 *     INTENÇÃO/RESULTADO (tabela `fulfillments`).
 *   - A concessão é idempotente: idempotency_key = "reference:product_id"
 *     (UNIQUE no banco + header Idempotency-Key na Game API). Reenvios não
 *     duplicam.
 *   - Se a Game API estiver indisponível OU o endpoint comercial ainda não
 *     existir (ver docs/api/commercial-integration.md), o fulfillment fica
 *     `pending` com retry (backoff). NUNCA simula sucesso.
 *   - Falhas DEFINITIVAS (400/404/409/422) marcam `failed` sem retry.
 *
 * Modo `commerce_endpoint` (OPCIONAL): usa um endpoint dedicado de concessão
 * server-to-server. Esse endpoint NÃO faz parte do contrato oficial atual da
 * Game API (o modelo oficial concede via webhook do provedor -> Game API, ver
 * NullFulfillmentAdapter e docs/api/commercial-integration.md). Esta classe é
 * uma abstração preparada para quando/se a Game API expuser tal endpoint —
 * nunca deve ser o modo padrão sem contrato oficial. Não inventa produção.
 *
 * Endpoint consumido neste modo: POST /commerce/fulfillments (a implementar).
 */
class GameFulfillmentService implements GameFulfillmentInterface
{
    private const ENDPOINT = '/commerce/fulfillments';

    private HttpClient $http;
    private Fulfillment $fulfillments;
    private Order $orders;
    private OrderEvent $events;

    public function __construct(
        ?HttpClient $http = null,
        ?Fulfillment $fulfillments = null,
        ?Order $orders = null,
        ?OrderEvent $events = null
    ) {
        $this->http = $http ?? new HttpClient(GameApiConfig::timeout());
        $this->fulfillments = $fulfillments ?? new Fulfillment();
        $this->orders = $orders ?? new Order();
        $this->events = $events ?? new OrderEvent();
    }

    public function mode(): string
    {
        return 'commerce_endpoint';
    }

    /**
     * Cria (idempotente) os fulfillments de um pedido pago e tenta conceder.
     * Chamado após confirmação REAL de pagamento aprovado.
     *
     * @param array $order Linha de orders (id, reference, player_id, currency, total_cents).
     * @param array $items Linhas de order_items (product_id, sku, quantity).
     */
    public function fulfillOrder(array $order, array $items): void
    {
        $orderId = (int) $order['id'];
        $reference = (string) $order['reference'];
        $playerId = (string) $order['player_id'];

        $anyPending = false;
        $anyFailed = false;
        $allFulfilled = true;

        foreach ($items as $item) {
            $productId = (string) $item['product_id'];
            $key = Fulfillment::keyFor($reference, $productId);

            $ensured = $this->fulfillments->ensure([
                'order_id'      => $orderId,
                'order_item_id' => (int) ($item['id'] ?? 0) ?: null,
                'product_id'    => $productId,
                'player_id'     => $playerId,
                'idempotency_key' => $key,
                'max_attempts'  => PaymentConfig::fulfillmentMaxAttempts(),
            ]);

            $row = $this->fulfillments->find($ensured['id']);
            if ($row && $row['status'] === 'fulfilled') {
                // Já concedido anteriormente (idempotência) — não repete.
                continue;
            }

            $outcome = $this->attemptGrant($order, $item, $key);
            $this->applyOutcome($orderId, $ensured['id'], $reference, $productId, $outcome);

            if ($outcome->isSuccess()) {
                continue;
            }
            $allFulfilled = false;
            if ($outcome->isPermanentFailure()) {
                $anyFailed = true;
            } else {
                $anyPending = true;
            }
        }

        // Reflete o estado agregado no pedido.
        $status = $allFulfilled ? 'fulfilled' : ($anyPending ? 'pending' : ($anyFailed ? 'failed' : 'processing'));
        $this->orders->updateStates($orderId, ['fulfillment_status' => $status]);
    }

    /**
     * Reprocessa um único fulfillment (retry manual do admin ou job de retry).
     */
    public function retry(array $fulfillmentRow, array $order, array $item): FulfillmentOutcome
    {
        $key = (string) $fulfillmentRow['idempotency_key'];
        $this->fulfillments->markProcessing((int) $fulfillmentRow['id']);
        $outcome = $this->attemptGrant($order, $item, $key);
        $this->applyOutcome(
            (int) $order['id'],
            (int) $fulfillmentRow['id'],
            (string) $order['reference'],
            (string) $item['product_id'],
            $outcome
        );
        return $outcome;
    }

    /**
     * Solicita a REVOGAÇÃO da concessão à Game API (após refund/chargeback com
     * política 'revoke'). Quem revoga o entitlement é a Game API, nunca o site.
     * Idempotente por Idempotency-Key = "reference:refund:product_id".
     *
     * @param string $policy 'revoke' (remover entitlement) ou 'keep' (só registrar).
     * @return bool true se a Game API confirmou (revoked/kept/accepted).
     */
    public function revokeOrder(array $order, array $items, string $policy = 'revoke', string $reason = 'refund'): bool
    {
        $orderId = (int) $order['id'];
        $reference = (string) $order['reference'];

        // Política 'keep': não revoga entitlement; apenas registra na timeline.
        if ($policy === 'keep') {
            $this->events->record($orderId, 'refund.keep', 'Reembolso sem revogação de itens (política keep)', 'admin');
            return true;
        }

        if (!GameApiConfig::isEnabled() || PaymentConfig::gameServiceClientId() === '') {
            $this->events->record($orderId, 'revoke.pending', 'Revogação pendente: integração não configurada', 'system');
            return false;
        }

        $ok = true;
        foreach ($items as $item) {
            $productId = (string) $item['product_id'];
            $idem = $reference . ':refund:' . $productId;
            $body = [
                'order_reference'   => $reference,
                'player_id'         => (string) $order['player_id'],
                'reason'            => $reason,
                'payment_reference' => (string) ($order['payment_reference'] ?? ''),
                'items'             => [[
                    'product_id' => $productId,
                    'sku'        => $item['sku'] ?? null,
                    'quantity'   => (int) ($item['quantity'] ?? 1),
                ]],
                'policy'            => 'revoke',
            ];
            $url = UrlGuard::normalizeBaseUrl(GameApiConfig::baseUrl()) . '/commerce/refunds';
            $headers = [
                'X-Client-Id'     => PaymentConfig::gameServiceClientId(),
                'Authorization'   => 'Bearer ' . PaymentConfig::gameServiceSecret(),
                'Idempotency-Key' => $idem,
            ];
            $response = $this->http->request('POST', $url, $headers, $body);

            if ($response->isTransportError() || $response->status < 200 || $response->status >= 300) {
                $ok = false;
                $this->events->record($orderId, 'revoke.pending', 'Falha ao revogar item (retry manual)', 'system', ['product_id' => $productId]);
                Logger::warning('revoke.failed', ['reference' => $reference, 'status' => $response->status]);
                continue;
            }

            $data = $response->json();
            $status = is_array($data) ? (string) ($data['status'] ?? 'revoked') : 'revoked';
            $externalId = is_array($data) ? ($data['refund_id'] ?? null) : null;

            $key = Fulfillment::keyFor($reference, $productId);
            $ff = $this->fulfillments->findByKey($key);
            if ($ff) {
                $this->fulfillments->markRevoked((int) $ff['id'], $externalId !== null ? (string) $externalId : null);
            }
            $this->events->record($orderId, 'revoke.done', 'Item revogado pela Game API', 'admin', [
                'product_id' => $productId,
                'status' => $status,
            ]);
        }

        if ($ok) {
            $this->orders->updateStates($orderId, ['fulfillment_status' => 'revoked']);
        }
        return $ok;
    }

    /**
     * Executa a chamada à Game API e interpreta o resultado, SEM tocar em
     * entitlement local. Traduz status HTTP em estados de fulfillment conforme
     * o contrato (docs/api/commercial-integration.md).
     */
    private function attemptGrant(array $order, array $item, string $idempotencyKey): FulfillmentOutcome
    {
        // Integração desativada ou service account não configurada -> pending
        // (nunca simula sucesso). Fica aguardando configuração/Game API.
        if (!GameApiConfig::isEnabled() || PaymentConfig::gameServiceClientId() === '') {
            return new FulfillmentOutcome('pending', null, 'Integração de fulfillment não configurada.');
        }

        $body = [
            'order_reference'   => (string) $order['reference'],
            'player_id'         => (string) $order['player_id'],
            'payment_reference' => (string) ($order['payment_reference'] ?? ''),
            'currency'          => (string) ($order['currency'] ?? 'BRL'),
            'amount'            => (int) ($item['line_total_cents'] ?? $order['total_cents'] ?? 0),
            'items'             => [[
                'product_id' => (string) $item['product_id'],
                'sku'        => $item['sku'] ?? null,
                'quantity'   => (int) ($item['quantity'] ?? 1),
            ]],
            'occurred_at'       => date('c'),
        ];

        $url = UrlGuard::normalizeBaseUrl(GameApiConfig::baseUrl()) . self::ENDPOINT;
        $headers = [
            'X-Client-Id'     => PaymentConfig::gameServiceClientId(),
            // Secret da service account como credencial Bearer (server-to-server).
            'Authorization'   => 'Bearer ' . PaymentConfig::gameServiceSecret(),
            // Idempotência exigida pelo contrato: mesma chave = mesma concessão.
            'Idempotency-Key' => $idempotencyKey,
        ];

        $response = $this->http->request('POST', $url, $headers, $body);

        // Erro de transporte (timeout/conexão/sem cURL) -> transitório.
        if ($response->isTransportError() || $response->status === 0) {
            Logger::warning('fulfillment.transient', ['reference' => $order['reference'], 'error' => $response->transportError]);
            return new FulfillmentOutcome('pending', null, 'Game API indisponível.');
        }

        $status = $response->status;

        // Indisponibilidade/limite -> transitório (retry).
        if ($status >= 500 || $status === 429) {
            Logger::warning('fulfillment.transient', ['reference' => $order['reference'], 'status' => $status]);
            return new FulfillmentOutcome('pending', null, 'Game API indisponível (HTTP ' . $status . ').');
        }

        // Erros DEFINITIVOS: não fazer retry (payload/produto/jogador/conflito/regra).
        if (in_array($status, [400, 401, 403, 404, 409, 422], true)) {
            $msg = $this->errorMessage($response) ?? ('HTTP ' . $status);
            Logger::error('fulfillment.permanent_failure', ['reference' => $order['reference'], 'status' => $status]);
            return new FulfillmentOutcome('failed', null, $msg);
        }

        if ($status < 200 || $status >= 300) {
            // Qualquer outro status inesperado: transitório por segurança.
            return new FulfillmentOutcome('pending', null, 'Resposta inesperada (HTTP ' . $status . ').');
        }

        $data = $response->json();
        // Alguns contratos aninham em data{}; aceita ambos.
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        $grantStatus = is_array($data) ? (string) ($data['status'] ?? 'granted') : 'granted';
        $externalId = is_array($data) ? ($data['fulfillment_id'] ?? null) : null;
        $replay = is_array($data) ? (bool) ($data['idempotent_replay'] ?? false) : false;

        if (in_array($grantStatus, ['granted', 'fulfilled'], true)) {
            return new FulfillmentOutcome('fulfilled', $externalId !== null ? (string) $externalId : null, null, $replay);
        }
        if (in_array($grantStatus, ['accepted', 'processing'], true)) {
            return new FulfillmentOutcome('processing', $externalId !== null ? (string) $externalId : null, null, $replay);
        }
        // Status de negócio desconhecido: transitório (reconsulta depois).
        return new FulfillmentOutcome('pending', null, 'Status inesperado: ' . $grantStatus);
    }

    /**
     * Extrai a mensagem de erro do corpo (envelope {error:{message}} ou {message}).
     */
    private function errorMessage(\App\Services\GameApi\HttpResponse $response): ?string
    {
        $json = $response->json();
        if (!is_array($json)) {
            return null;
        }
        $msg = $json['error']['message'] ?? $json['message'] ?? null;
        return is_string($msg) && $msg !== '' ? substr($msg, 0, 255) : null;
    }

    /**
     * Persiste o resultado da tentativa no fulfillment e na timeline.
     */
    private function applyOutcome(int $orderId, int $fulfillmentId, string $reference, string $productId, FulfillmentOutcome $outcome): void
    {
        if ($outcome->isSuccess()) {
            $this->fulfillments->markFulfilled($fulfillmentId, $outcome->externalId);
            $this->events->record($orderId, 'fulfillment.fulfilled', 'Item concedido pela Game API', 'system', [
                'product_id' => $productId,
                'external_id' => $outcome->externalId,
                'idempotent_replay' => $outcome->idempotentReplay,
            ]);
            return;
        }

        if ($outcome->isPermanentFailure()) {
            $this->fulfillments->markFailure($fulfillmentId, (string) $outcome->error, true, null);
            $this->events->record($orderId, 'fulfillment.failed', 'Falha definitiva na concessão', 'system', [
                'product_id' => $productId,
                'error' => $outcome->error,
            ]);
            return;
        }

        // Transitório (pending/processing): agenda retry com backoff exponencial.
        $row = $this->fulfillments->find($fulfillmentId);
        $attempts = $row ? (int) $row['attempts'] : 0;
        $next = $this->backoffTimestamp($attempts + 1);
        $this->fulfillments->markFailure($fulfillmentId, (string) ($outcome->error ?? 'aguardando Game API'), false, $next);
        $this->events->record($orderId, 'fulfillment.pending', 'Concessão pendente (retry agendado)', 'system', [
            'product_id' => $productId,
            'next_attempt_at' => $next,
        ]);
    }

    /**
     * Backoff exponencial (cap 1h) a partir do número da tentativa.
     */
    private function backoffTimestamp(int $attempt): string
    {
        $delaySeconds = min(3600, (int) (30 * (2 ** max(0, $attempt - 1))));
        return date('Y-m-d H:i:s', time() + $delaySeconds);
    }
}
