<?php

namespace App\Services\Commerce;

/**
 * Contrato de fulfillment (concessão de itens de um pedido pago).
 *
 * A concessão de entitlement é AUTORIDADE da Game API — o site nunca concede
 * localmente. Este contrato existe para abstrair COMO a concessão é acionada,
 * porque o modelo pode variar conforme a infraestrutura da Game API:
 *
 *  - `game_webhook` (modelo OFICIAL, padrão): o provedor de pagamento chama o
 *    webhook da própria Game API (`POST /payments/webhooks/:provider`), que
 *    valida a assinatura, confirma o pedido e concede o entitlement (idempotente).
 *    Nesse modelo o site NÃO solicita concessão — apenas registra o pedido e
 *    confirma a posse consultando `GET /player/entitlements`.
 *
 *  - `commerce_endpoint` (opcional/futuro): a Game API expõe um endpoint
 *    dedicado de concessão server-to-server. Só usar se/quando existir contrato
 *    oficial para isso (ver docs/api/commercial-integration.md). NÃO inventar
 *    endpoint em produção.
 *
 * Nenhuma implementação insere/edita entitlement no banco do site.
 */
interface GameFulfillmentInterface
{
    /**
     * Identificador do modo (game_webhook | commerce_endpoint | null).
     */
    public function mode(): string;

    /**
     * Aciona a concessão dos itens de um pedido pago, conforme o modo.
     * Registra o resultado/intenção na timeline do pedido — nunca concede
     * localmente.
     *
     * @param array $order Linha de orders (id, reference, player_id, currency, total_cents, payment_reference).
     * @param array $items Linhas de order_items (product_id, sku, quantity).
     */
    public function fulfillOrder(array $order, array $items): void;
}
