<?php

namespace App\Models;

use App\Core\Model;

/**
 * Registro da INTENÇÃO/RESULTADO de concessão via Game API.
 *
 * NUNCA guarda o entitlement em si (isso é autoridade exclusiva da Game API).
 * idempotency_key (UNIQUE) = "reference:product_id" garante 1 concessão por
 * item, mesmo com N retries/webhooks duplicados.
 */
class Fulfillment extends Model
{
    protected string $table = 'fulfillments';
    protected array $fillable = [
        'order_id', 'order_item_id', 'product_id', 'player_id', 'idempotency_key',
        'status', 'attempts', 'max_attempts', 'external_id', 'last_error',
        'permanent_failure', 'next_attempt_at', 'fulfilled_at', 'revoked_at',
    ];

    public const STATUSES = ['pending', 'processing', 'fulfilled', 'failed', 'revoked'];

    public static function keyFor(string $orderReference, string $productId): string
    {
        return $orderReference . ':' . $productId;
    }

    public function findByKey(string $key): ?array
    {
        return $this->findBy('idempotency_key', $key);
    }

    /**
     * Cria o fulfillment de forma idempotente. Se a chave já existir, devolve o
     * existente (não duplica). Retorna ['id'=>int, 'created'=>bool].
     */
    public function ensure(array $data): array
    {
        $key = $data['idempotency_key'] ?? '';
        if ($key === '') {
            throw new \InvalidArgumentException('idempotency_key obrigatório.');
        }
        $existing = $this->findByKey($key);
        if ($existing) {
            return ['id' => (int) $existing['id'], 'created' => false];
        }
        // INSERT IGNORE cobre corrida concorrente (UNIQUE key).
        $this->db->execute(
            "INSERT IGNORE INTO `fulfillments`
                (`order_id`, `order_item_id`, `product_id`, `player_id`, `idempotency_key`, `status`, `max_attempts`)
             VALUES (:oid, :iid, :pid, :player, :key, 'pending', :maxa)",
            [
                'oid' => $data['order_id'],
                'iid' => $data['order_item_id'] ?? null,
                'pid' => $data['product_id'],
                'player' => $data['player_id'],
                'key' => $key,
                'maxa' => $data['max_attempts'] ?? 8,
            ]
        );
        $id = (int) $this->db->lastInsertId();
        if ($id === 0) {
            $row = $this->findByKey($key);
            return ['id' => $row ? (int) $row['id'] : 0, 'created' => false];
        }
        return ['id' => $id, 'created' => true];
    }

    /**
     * Marca sucesso da concessão (idempotente).
     */
    public function markFulfilled(int $id, ?string $externalId = null): int
    {
        return $this->db->execute(
            "UPDATE `fulfillments`
             SET `status` = 'fulfilled', `external_id` = COALESCE(:ext, `external_id`),
                 `fulfilled_at` = :now, `last_error` = NULL, `permanent_failure` = 0, `next_attempt_at` = NULL
             WHERE `id` = :id AND `status` <> 'fulfilled'",
            ['ext' => $externalId, 'now' => date('Y-m-d H:i:s'), 'id' => $id]
        );
    }

    /**
     * Registra uma falha. Se permanente, não agenda retry. Caso contrário,
     * incrementa tentativas e agenda o próximo attempt com backoff.
     */
    public function markFailure(int $id, string $error, bool $permanent, ?string $nextAttemptAt): int
    {
        return $this->db->execute(
            "UPDATE `fulfillments`
             SET `attempts` = `attempts` + 1,
                 `status` = :status,
                 `last_error` = :err,
                 `permanent_failure` = :perm,
                 `next_attempt_at` = :next
             WHERE `id` = :id",
            [
                'status' => $permanent ? 'failed' : 'pending',
                'err' => substr($error, 0, 255),
                'perm' => $permanent ? 1 : 0,
                'next' => $permanent ? null : $nextAttemptAt,
                'id' => $id,
            ]
        );
    }

    public function markProcessing(int $id): int
    {
        return $this->db->execute(
            "UPDATE `fulfillments` SET `status` = 'processing' WHERE `id` = :id AND `status` = 'pending'",
            ['id' => $id]
        );
    }

    public function markRevoked(int $id, ?string $externalId = null): int
    {
        return $this->db->execute(
            "UPDATE `fulfillments`
             SET `status` = 'revoked', `revoked_at` = :now, `external_id` = COALESCE(:ext, `external_id`)
             WHERE `id` = :id",
            ['now' => date('Y-m-d H:i:s'), 'ext' => $externalId, 'id' => $id]
        );
    }

    public function forOrder(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `fulfillments` WHERE `order_id` = :id ORDER BY `id` ASC",
            ['id' => $orderId]
        );
    }

    /**
     * Fulfillments pendentes e prontos para retry (respeitando backoff e limite).
     */
    public function dueForRetry(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db->fetchAll(
            "SELECT * FROM `fulfillments`
             WHERE `status` IN ('pending','processing')
               AND `permanent_failure` = 0
               AND `attempts` < `max_attempts`
               AND (`next_attempt_at` IS NULL OR `next_attempt_at` <= :now)
             ORDER BY `id` ASC LIMIT {$limit}",
            ['now' => date('Y-m-d H:i:s')]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM `fulfillments` WHERE `status` = :s",
            ['s' => $status]
        );
    }
}
