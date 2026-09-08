<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\OrderEvent;

/**
 * Fulfillment no modelo OFICIAL do Project Survival (`game_webhook`).
 *
 * A concessão de entitlement acontece na PRÓPRIA Game API: o provedor de
 * pagamento chama `POST /payments/webhooks/:provider` da Game API, que valida a
 * assinatura, confirma o pedido e concede o item (idempotente). Portanto o site
 * NÃO solicita concessão nem concede localmente.
 *
 * Este adapter apenas registra na timeline do pedido que a entrega é de
 * responsabilidade da Game API e marca o fulfillment como delegado. A posse
 * real é confirmada consultando `GET /player/entitlements`.
 */
class NullFulfillmentAdapter implements GameFulfillmentInterface
{
    private Order $orders;
    private OrderEvent $events;

    public function __construct(?Order $orders = null, ?OrderEvent $events = null)
    {
        $this->orders = $orders ?? new Order();
        $this->events = $events ?? new OrderEvent();
    }

    public function mode(): string
    {
        return 'game_webhook';
    }

    public function fulfillOrder(array $order, array $items): void
    {
        $orderId = (int) $order['id'];

        // Não concede e não chama endpoint algum. Apenas documenta na timeline
        // que a concessão é feita pela Game API (webhook do provedor -> API).
        $this->events->record(
            $orderId,
            'fulfillment.delegated',
            'Concessão delegada à Game API (webhook do provedor). O site não concede itens.',
            'system',
            ['products' => array_values(array_map(static fn ($i) => (string) ($i['product_id'] ?? ''), $items))]
        );

        // Estado "processing": a entrega é conduzida pela Game API; a posse é
        // confirmada via GET /player/entitlements na área da conta.
        $this->orders->updateStates($orderId, ['fulfillment_status' => 'processing']);
    }
}
