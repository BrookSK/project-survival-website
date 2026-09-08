<?php

namespace App\Services\Commerce;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;

/**
 * Cria pedidos com integridade: preço/moeda vêm SEMPRE da Game API (via
 * ProductPricing), o cupom é validado no backend, e uma idempotency_key
 * evita pedidos duplicados por duplo clique/reenvio.
 *
 * O pedido é criado ANTES do pagamento, em estado `pending`/`payment=none`.
 * NÃO cobra e NÃO concede itens — isso é papel do PaymentService/Fulfillment.
 */
class CheckoutService
{
    private Order $orders;
    private OrderItem $items;
    private OrderEvent $events;
    private Coupon $coupons;
    private ProductPricing $pricing;

    public function __construct(
        ?Order $orders = null,
        ?OrderItem $items = null,
        ?OrderEvent $events = null,
        ?Coupon $coupons = null,
        ?ProductPricing $pricing = null
    ) {
        $this->orders = $orders ?? new Order();
        $this->items = $items ?? new OrderItem();
        $this->events = $events ?? new OrderEvent();
        $this->coupons = $coupons ?? new Coupon();
        $this->pricing = $pricing ?? new ProductPricing();
    }

    /**
     * Cria (ou reaproveita, por idempotência) um pedido.
     *
     * @param string      $playerId    ID do jogador (sessão/Game API).
     * @param string      $productId   Produto desejado.
     * @param int         $quantity    Quantidade (>=1).
     * @param string|null $token       Bearer do jogador (para preço/owned ao vivo).
     * @param array       $context     ['email','ip','user_agent','coupon','terms_version','refund_terms_version','gateway']
     *
     * @return array Linha do pedido criado/existente (com 'items').
     *
     * @throws PricingException Se o produto não puder ser comprado.
     * @throws \InvalidArgumentException Em parâmetros inválidos.
     */
    public function createOrder(string $playerId, string $productId, int $quantity, ?string $token, array $context = []): array
    {
        if ($playerId === '') {
            throw new \InvalidArgumentException('Jogador não identificado.');
        }
        $quantity = max(1, min(99, $quantity));

        // Idempotência anti-duplo-clique: chave estável por jogador+produto+qtd+cupom.
        $idemKey = $context['idempotency_key'] ?? $this->buildIdempotencyKey($playerId, $productId, $quantity, (string) ($context['coupon'] ?? ''));
        $existing = $this->orders->findByIdempotencyKey($idemKey);
        if ($existing && in_array($existing['order_status'], ['pending', 'awaiting_payment'], true)) {
            // Reaproveita o pedido ainda aberto (não cria duplicado).
            $existing['items'] = $this->items->forOrder((int) $existing['id']);
            return $existing;
        }

        // Preço/moeda OFICIAIS da Game API (o navegador nunca informa preço).
        $priced = $this->pricing->resolve($productId, $token);

        $unit = (int) $priced['unit_price_cents'];
        $currency = (string) $priced['currency'];
        $subtotal = $unit * $quantity;

        // Cupom (validado no backend). Divergência = ignora o desconto pedido.
        $discount = 0;
        $couponRow = null;
        $couponCode = trim((string) ($context['coupon'] ?? ''));
        if ($couponCode !== '') {
            $result = $this->coupons->validateFor($couponCode, $subtotal, $currency, $playerId);
            if ($result['valid']) {
                $discount = (int) $result['discount_cents'];
                $couponRow = $result['coupon'];
            } else {
                // Cupom inválido não bloqueia o pedido; apenas não aplica desconto.
                $couponCode = '';
            }
        }

        $total = max(0, $subtotal - $discount);

        $order = $this->orders->transaction(function () use (
            $playerId, $priced, $unit, $currency, $quantity, $subtotal, $discount, $total,
            $couponRow, $couponCode, $idemKey, $context
        ) {
            $orderId = $this->orders->create([
                'reference'       => 'PENDING',
                'player_id'       => $playerId,
                'player_email'    => $context['email'] ?? null,
                'order_status'    => 'pending',
                'payment_status'  => 'none',
                'fulfillment_status' => 'none',
                'currency'        => $currency,
                'subtotal_cents'  => $subtotal,
                'discount_cents'  => $discount,
                'total_cents'     => $total,
                'coupon_id'       => $couponRow['id'] ?? null,
                'coupon_code'     => $couponCode !== '' ? $couponCode : null,
                'gateway'         => $context['gateway'] ?? null,
                'terms_version'   => $context['terms_version'] ?? null,
                'refund_terms_version' => $context['refund_terms_version'] ?? null,
                'idempotency_key' => $idemKey,
                'client_ip'       => $context['ip'] ?? null,
                'user_agent'      => isset($context['user_agent']) ? substr((string) $context['user_agent'], 0, 255) : null,
            ]);

            // Referência estável derivada do id (ex.: SITE-000123).
            $reference = Order::referenceFor($orderId);
            $this->orders->update($orderId, ['reference' => $reference]);

            $this->items->create([
                'order_id'         => $orderId,
                'product_id'       => $priced['product_id'],
                'sku'              => $priced['sku'],
                'name'             => $priced['name'],
                'unit_price_cents' => $unit,
                'currency'         => $currency,
                'quantity'         => $quantity,
                'line_total_cents' => $unit * $quantity,
                'metadata'         => json_encode($priced['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            if ($couponRow) {
                $this->coupons->redeem((int) $couponRow['id'], $orderId, $playerId, $discount);
            }

            $this->events->record($orderId, 'order.created', 'Pedido criado', 'player', [
                'reference' => $reference,
                'total_cents' => $total,
                'currency' => $currency,
            ]);

            return $this->orders->find($orderId);
        });

        $order['items'] = $this->items->forOrder((int) $order['id']);
        return $order;
    }

    /**
     * Cancela um pedido ainda não pago (libera cupom). NÃO afeta pagamentos
     * aprovados.
     */
    public function cancelOpenOrder(int $orderId): bool
    {
        $order = $this->orders->find($orderId);
        if (!$order || !in_array($order['order_status'], ['pending', 'awaiting_payment'], true)) {
            return false;
        }
        $this->coupons->releaseRedemption($orderId);
        $this->orders->updateStates($orderId, ['order_status' => 'cancelled']);
        $this->events->record($orderId, 'order.cancelled', 'Pedido cancelado antes do pagamento', 'player');
        return true;
    }

    private function buildIdempotencyKey(string $playerId, string $productId, int $quantity, string $coupon): string
    {
        return substr(hash('sha256', $playerId . '|' . $productId . '|' . $quantity . '|' . strtoupper($coupon)), 0, 40);
    }
}
