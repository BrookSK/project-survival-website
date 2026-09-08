<?php

namespace App\Models;

use App\Core\Model;

/**
 * Transação de pagamento junto ao gateway. external_id é o ID no gateway.
 * O par (gateway, external_id) é UNIQUE para deduplicar.
 */
class PaymentTransaction extends Model
{
    protected string $table = 'payment_transactions';
    protected array $fillable = [
        'order_id', 'gateway', 'external_id', 'method', 'status',
        'amount_cents', 'currency', 'refunded_cents', 'raw_snapshot', 'processed_at',
    ];

    public const STATUSES = ['pending', 'in_process', 'approved', 'rejected', 'cancelled', 'refunded', 'charged_back'];

    public function findByExternal(string $gateway, string $externalId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `payment_transactions` WHERE `gateway` = :g AND `external_id` = :e LIMIT 1",
            ['g' => $gateway, 'e' => $externalId]
        );
    }

    public function forOrder(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `payment_transactions` WHERE `order_id` = :id ORDER BY `id` ASC",
            ['id' => $orderId]
        );
    }

    /**
     * Insere ou atualiza a transação pelo par (gateway, external_id).
     *
     * @param array $data Chaves: order_id, gateway, external_id, method, status,
     *                    amount_cents, currency, refunded_cents, raw_snapshot
     */
    public function upsertByExternal(array $data): int
    {
        $existing = null;
        if (!empty($data['external_id'])) {
            $existing = $this->findByExternal($data['gateway'], (string) $data['external_id']);
        }
        $data['processed_at'] = date('Y-m-d H:i:s');
        if ($existing) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        return $this->create($data);
    }

    public function updateStatus(int $id, string $status, ?int $refundedCents = null): int
    {
        $sets = ['`status` = :status', '`processed_at` = :now'];
        $params = ['status' => $status, 'now' => date('Y-m-d H:i:s'), 'id' => $id];
        if ($refundedCents !== null) {
            $sets[] = '`refunded_cents` = :ref';
            $params['ref'] = $refundedCents;
        }
        return $this->db->execute(
            "UPDATE `payment_transactions` SET " . implode(', ', $sets) . " WHERE `id` = :id",
            $params
        );
    }

    public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = '`status` = :s';
            $params['s'] = $filters['status'];
        }
        $clause = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `payment_transactions`{$clause}", $params);
        $rows = $this->db->fetchAll(
            "SELECT * FROM `payment_transactions`{$clause} ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }
}
