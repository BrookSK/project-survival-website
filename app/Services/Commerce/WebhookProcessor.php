<?php

namespace App\Services\Commerce;

use App\Core\Logger;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentTransaction;
use App\Models\WebhookEvent;
use App\Services\GameApi\HttpClient;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaymentStatus;

/**
 * Processa webhooks de pagamento com segurança e idempotência.
 *
 * Garantias (regras do Prompt 6):
 *   - NUNCA trata o payload do webhook como prova de pagamento. Ao receber um
 *     evento válido (assinatura OK), consulta o gateway para obter o estado
 *     REAL (PaymentService::syncFromGateway).
 *   - Idempotência: webhook_events(provider,event_id) UNIQUE. 10 webhooks
 *     idênticos = 1 processamento lógico.
 *   - Assinatura inválida -> registra como `invalid` e NÃO processa.
 *   - Só dispara fulfillment quando o pagamento está REALMENTE aprovado; e o
 *     fulfillment em si é idempotente (não concede duas vezes).
 *   - Responde rápido: o processamento é curto e não bloqueia.
 */
class WebhookProcessor
{
    private PaymentService $payments;
    private GameFulfillmentInterface $fulfillment;
    private WebhookEvent $webhooks;
    private Order $orders;
    private PaymentTransaction $transactions;
    private OrderEvent $events;

    public function __construct(
        ?PaymentService $payments = null,
        ?GameFulfillmentInterface $fulfillment = null,
        ?WebhookEvent $webhooks = null,
        ?Order $orders = null,
        ?PaymentTransaction $transactions = null,
        ?OrderEvent $events = null,
        ?HttpClient $http = null
    ) {
        $this->payments = $payments ?? new PaymentService(null, $http);
        // Modo oficial (game_webhook): a concessão é da Game API — o site não
        // concede. Ver FulfillmentResolver / docs/api/commercial-integration.md.
        $this->fulfillment = $fulfillment ?? FulfillmentResolver::resolve($http);
        $this->webhooks = $webhooks ?? new WebhookEvent();
        $this->orders = $orders ?? new Order();
        $this->transactions = $transactions ?? new PaymentTransaction();
        $this->events = $events ?? new OrderEvent();
    }

    /**
     * Processa uma notificação recebida. Retorna um resultado simbólico:
     *   'invalid'   -> assinatura inválida (rejeitar).
     *   'duplicate' -> já processado antes (idempotência).
     *   'ignored'   -> válido, mas sem pagamento acionável.
     *   'processed' -> processado (estado sincronizado com o gateway).
     *
     * Sempre responde 200 para eventos aceitáveis (evita reentrega infinita),
     * exceto assinatura inválida, que deve retornar erro ao gateway.
     */
    public function process(string $provider, array $headers, string $rawBody, array $query = []): string
    {
        $parsed = $this->payments->parseWebhook($headers, $rawBody, $query);

        // Assinatura inválida: registra e rejeita. NUNCA processa.
        if (!$parsed->signatureValid) {
            $eventId = $parsed->eventId ?? ('invalid_' . substr(hash('sha256', $rawBody), 0, 24));
            $this->webhooks->registerArrival($provider, $eventId, $parsed->eventType, $parsed->externalPaymentId, false, $this->safePayload($parsed->raw));
            Logger::warning('webhook.invalid_signature', ['provider' => $provider]);
            return 'invalid';
        }

        // Idempotência: um event_id por processamento lógico.
        $eventId = $parsed->eventId ?? ('evt_' . substr(hash('sha256', $rawBody), 0, 24));
        $arrival = $this->webhooks->registerArrival(
            $provider,
            $eventId,
            $parsed->eventType,
            $parsed->externalPaymentId,
            true,
            $this->safePayload($parsed->raw)
        );
        if ($arrival['duplicate']) {
            // Já recebido: não reprocessa (idempotência).
            return 'duplicate';
        }

        // Sem pagamento referenciado: nada acionável.
        if (!$parsed->isActionable()) {
            $this->webhooks->markProcessed($arrival['id'], 'ignored', null, 'Sem pagamento referenciado');
            return 'ignored';
        }

        $externalId = (string) $parsed->externalPaymentId;

        // Localiza a transação/pedido. Se não houver, ignora (não concede nada).
        $tx = $this->transactions->findByExternal($provider, $externalId);
        if (!$tx) {
            // Alguns fluxos criam a transação só aqui; tentamos achar via consulta
            // do gateway seria ideal, mas sem transação prévia não há pedido conhecido.
            $this->webhooks->markProcessed($arrival['id'], 'ignored', null, 'Transação desconhecida');
            Logger::warning('webhook.unknown_transaction', ['provider' => $provider, 'external_id' => $externalId]);
            return 'ignored';
        }

        $orderId = (int) $tx['order_id'];

        // Consulta o gateway para obter o estado REAL (nunca confia no payload).
        try {
            $intent = $this->payments->syncFromGateway($orderId, $externalId);
        } catch (PaymentException $e) {
            // Não conseguimos confirmar: registra e deixa para reprocessar depois.
            $this->webhooks->markProcessed($arrival['id'], 'failed', $orderId, 'Falha ao confirmar no gateway');
            Logger::warning('webhook.sync_failed', ['provider' => $provider, 'order_id' => $orderId]);
            return 'processed';
        }

        // Só concede quando REALMENTE aprovado. O fulfillment é idempotente.
        if (PaymentStatus::isFinalApproved($intent->status)) {
            $this->triggerFulfillment($orderId);
        } elseif ($intent->status === PaymentStatus::REFUNDED || $intent->status === PaymentStatus::CHARGED_BACK) {
            $this->events->record($orderId, 'payment.' . $intent->status, 'Gateway sinalizou estorno/chargeback', 'gateway:' . $provider);
        }

        $this->webhooks->markProcessed($arrival['id'], 'processed', $orderId, 'Estado confirmado: ' . $intent->status);
        return 'processed';
    }

    /**
     * Dispara a concessão via Game API (idempotente). Não concede itens
     * localmente — apenas solicita à autoridade (Game API).
     */
    private function triggerFulfillment(int $orderId): void
    {
        $order = $this->orders->find($orderId);
        if (!$order) {
            return;
        }
        // Já entregue: nada a fazer (idempotência de alto nível).
        if ($order['fulfillment_status'] === 'fulfilled') {
            return;
        }
        $items = $this->orders->items($orderId);
        // Anexa a referência de pagamento para rastreio na Game API.
        $tx = $this->transactions->forOrder($orderId);
        $latest = $tx ? end($tx) : null;
        $order['payment_reference'] = $latest['external_id'] ?? '';

        $this->fulfillment->fulfillOrder($order, $items);
    }

    /**
     * Serializa o payload de forma segura para persistência (sem segredos).
     */
    private function safePayload(array $raw): ?string
    {
        if ($raw === []) {
            return null;
        }
        $json = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? substr($json, 0, 60000) : null;
    }
}
