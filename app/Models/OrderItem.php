<?php

namespace App\Models;

use App\Core\Model;

/**
 * Linha de um pedido. Snapshot IMUTÁVEL de preço/moeda vindo da Game API no
 * momento do pedido — o navegador NUNCA informa preço.
 */
class OrderItem extends Model
{
    protected string $table = 'order_items';
    protected array $fillable = [
        'order_id', 'product_id', 'sku', 'name',
        'unit_price_cents', 'currency', 'quantity', 'line_total_cents', 'metadata',
    ];

    public function forOrder(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `order_items` WHERE `order_id` = :id ORDER BY `id` ASC",
            ['id' => $orderId]
        );
    }
}
