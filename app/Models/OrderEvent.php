<?php

namespace App\Models;

use App\Core\Model;

/**
 * Timeline auditável do pedido (append-only). Nunca guarda segredos.
 */
class OrderEvent extends Model
{
    protected string $table = 'order_events';
    protected array $fillable = ['order_id', 'type', 'message', 'actor', 'data'];

    /**
     * Adiciona um evento à timeline.
     *
     * @param array $data Dados livres (serão serializados em JSON, sem segredos).
     */
    public function record(int $orderId, string $type, ?string $message = null, string $actor = 'system', array $data = []): int
    {
        return $this->create([
            'order_id' => $orderId,
            'type'     => $type,
            'message'  => $message !== null ? substr($message, 0, 255) : null,
            'actor'    => substr($actor, 0, 60),
            'data'     => $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }

    public function forOrder(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `order_events` WHERE `order_id` = :id ORDER BY `id` ASC",
            ['id' => $orderId]
        );
    }
}
