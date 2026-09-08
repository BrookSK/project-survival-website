<?php

namespace App\Services\Payments;

use App\Core\Logger;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentTransaction;
use App\Services\GameApi\HttpClient;

/**
 * Orquestra a comunicação com o gateway e a persistência das transações.
 *
 * Regras invioláveis:
 *   - NUNCA simula pagamento. O status financeiro vem SEMPRE do gateway
 *     (createPayment/getPayment), nunca do navegador nem do payload do webhook.
 *   - A confirmação de um webhook consulta o gateway para obter o estado REAL.
 *   - Este serviço NÃO concede itens: isso é responsabilidade do
 *     GameFulfillmentService (adapter da Game API).
 */
class PaymentService
{
    private PaymentGatewayInterface $gateway;
    private Order $orders;
    private PaymentTransaction $transactions;
    private OrderEvent $events;

    public function __construct(
        ?PaymentGatewayInterface $gateway = null,
        ?HttpClient $http = null,
        ?Order $orders = null,
        ?PaymentTransaction $transactions = null,
        ?OrderEvent $events = null
    ) {
        $this->gateway = $gateway ?? PaymentGatewayManager::resolve($http);
        $this->orders = $orders ?? new Order();
        $this->transactions = $transactions ?? new PaymentTransaction();
        $this->events = $events ?? new OrderEvent();
    }

    public function gatewayName(): string
    {
        return $this->gateway->name();
    }

    public function isEnabled(): bool
    {
        return $this->gateway->isEnabled();
    }

    /**
     * Cria o pagamento no gateway para um pedido já persistido e registra a
     * transação. Devolve o PaymentIntent para conduzir o checkout.
     *
     * @throws PaymentException
     */
    public function createForOrder(array $order, string $method, array $options = []): PaymentIntent
    {
        $intent = $this->gateway->createPayment($order, $method, $options);

        $this->transactions->upsertByExternal([
            'order_id'     => (int) $order['id'],
            'gateway'      => $this->gateway->name(),
            'external_id'  => $intent->externalId,
            'method'       => $intent->method,
            'status'       => $this->mapTxStatus($intent->status),
            'amount_cents' => $intent->amountCents,
            'currency'     => $intent->currency,
            'raw_snapshot' => $this->encodeSnapshot($intent->raw),
        ]);

        $this->events->record(
            (int) $order['id'],
            'payment.created',
            'Pagamento criado no gateway (' . $this->gateway->name() . ')',
            'system',
            ['external_id' => $intent->externalId, 'method' => $intent->method, 'status' => $intent->status]
        );

        // Espelha o status de pagamento no pedido (sem simular aprovação).
        $this->orders->updateStates((int) $order['id'], [
            'payment_status' => $this->mapTxStatus($intent->status),
            'order_status'   => $intent->status === PaymentStatus::APPROVED ? 'paid' : 'awaiting_payment',
        ]);

        return $intent;
    }

    /**
     * Consulta o estado REAL de um pagamento no gateway (fonte de verdade).
     *
     * @throws PaymentException
     */
    public function fetchRealStatus(string $externalId): PaymentIntent
    {
        return $this->gateway->getPayment($externalId);
    }

    /**
     * Sincroniza o estado de uma transação/pedido a partir do estado REAL do
     * gateway. Retorna o PaymentIntent consultado. NÃO concede itens.
     *
     * @throws PaymentException
     */
    public function syncFromGateway(int $orderId, string $externalId): PaymentIntent
    {
        $intent = $this->gateway->getPayment($externalId);
        $txStatus = $this->mapTxStatus($intent->status);

        $this->transactions->upsertByExternal([
            'order_id'     => $orderId,
            'gateway'      => $this->gateway->name(),
            'external_id'  => $intent->externalId,
            'method'       => $intent->method,
            'status'       => $txStatus,
            'amount_cents' => $intent->amountCents,
            'currency'     => $intent->currency,
            'raw_snapshot' => $this->encodeSnapshot($intent->raw),
        ]);

        $states = ['payment_status' => $txStatus];
        if ($intent->status === PaymentStatus::APPROVED) {
            $states['order_status'] = 'paid';
            $states['paid_at'] = date('Y-m-d H:i:s');
        } elseif (PaymentStatus::isFailure($intent->status)) {
            $states['order_status'] = $intent->status === PaymentStatus::CANCELLED ? 'cancelled' : 'failed';
        } elseif ($intent->status === PaymentStatus::REFUNDED) {
            $states['order_status'] = 'refunded';
        } elseif ($intent->status === PaymentStatus::CHARGED_BACK) {
            $states['order_status'] = 'refunded';
        }
        $this->orders->updateStates($orderId, $states);

        $this->events->record(
            $orderId,
            'payment.' . $intent->status,
            'Estado confirmado junto ao gateway',
            'gateway:' . $this->gateway->name(),
            ['external_id' => $intent->externalId, 'status' => $intent->status]
        );

        return $intent;
    }

    /**
     * Analisa um webhook (valida assinatura, extrai IDs). NÃO decide status.
     */
    public function parseWebhook(array $headers, string $rawBody, array $query = []): WebhookResult
    {
        return $this->gateway->parseWebhook($headers, $rawBody, $query);
    }

    /**
     * Solicita estorno ao gateway e registra a transação de reembolso.
     *
     * @throws PaymentException
     */
    public function refund(int $orderId, string $externalId, ?int $amountCents = null): RefundResult
    {
        $result = $this->gateway->refundPayment($externalId, $amountCents);

        $tx = $this->transactions->findByExternal($this->gateway->name(), $externalId);
        if ($tx) {
            $refunded = ((int) $tx['refunded_cents']) + ($result->refundedCents ?: (int) $tx['amount_cents']);
            $this->transactions->updateStatus((int) $tx['id'], PaymentStatus::REFUNDED, $refunded);
        }

        $this->events->record(
            $orderId,
            'refund.done',
            'Estorno solicitado ao gateway',
            'admin',
            ['external_id' => $externalId, 'refunded_cents' => $result->refundedCents]
        );

        return $result;
    }

    /**
     * Mapeia o status normalizado do gateway para o enum de payment_status do
     * pedido/transação (mesmos valores).
     */
    private function mapTxStatus(string $status): string
    {
        return PaymentStatus::normalize($status);
    }

    /**
     * Serializa o snapshot para JSON (já sanitizado pelo gateway).
     */
    private function encodeSnapshot(array $raw): ?string
    {
        if ($raw === []) {
            return null;
        }
        $json = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : null;
    }
}
