<?php

namespace App\Models;

use App\Core\Model;

/**
 * Log e idempotência dos webhooks do gateway. O par (provider, event_id) é
 * UNIQUE: 10 webhooks idênticos = 1 processamento lógico.
 *
 * O payload é apenas registro — NUNCA é prova de pagamento. O estado real é
 * confirmado consultando o gateway.
 */
class WebhookEvent extends Model
{
    protected string $table = 'webhook_events';
    protected array $fillable = [
        'provider', 'event_id', 'event_type', 'external_ref', 'order_id',
        'status', 'signature_valid', 'payload', 'note', 'processed_at',
    ];

    /**
     * Registra a chegada de um evento. Retorna:
     *   ['id' => int, 'duplicate' => bool]
     * duplicate=true quando o (provider, event_id) já existia (idempotência).
     */
    public function registerArrival(
        string $provider,
        string $eventId,
        ?string $eventType,
        ?string $externalRef,
        bool $signatureValid,
        ?string $payloadJson
    ): array {
        $existing = $this->db->fetch(
            "SELECT `id` FROM `webhook_events` WHERE `provider` = :p AND `event_id` = :e LIMIT 1",
            ['p' => $provider, 'e' => $eventId]
        );
        if ($existing) {
            return ['id' => (int) $existing['id'], 'duplicate' => true];
        }
        // INSERT IGNORE cobre corrida concorrente (UNIQUE key).
        $this->db->execute(
            "INSERT IGNORE INTO `webhook_events`
                (`provider`, `event_id`, `event_type`, `external_ref`, `status`, `signature_valid`, `payload`)
             VALUES (:p, :e, :t, :r, 'received', :sig, :payload)",
            [
                'p' => $provider,
                'e' => $eventId,
                't' => $eventType,
                'r' => $externalRef,
                'sig' => $signatureValid ? 1 : 0,
                'payload' => $payloadJson,
            ]
        );
        $id = (int) $this->db->lastInsertId();
        if ($id === 0) {
            // Corrida: outra requisição inseriu primeiro -> tratar como duplicado.
            $row = $this->db->fetch(
                "SELECT `id` FROM `webhook_events` WHERE `provider` = :p AND `event_id` = :e LIMIT 1",
                ['p' => $provider, 'e' => $eventId]
            );
            return ['id' => $row ? (int) $row['id'] : 0, 'duplicate' => true];
        }
        return ['id' => $id, 'duplicate' => false];
    }

    public function markProcessed(int $id, string $status, ?int $orderId = null, ?string $note = null): int
    {
        return $this->db->execute(
            "UPDATE `webhook_events`
             SET `status` = :status, `order_id` = COALESCE(:oid, `order_id`),
                 `note` = COALESCE(:note, `note`), `processed_at` = :now
             WHERE `id` = :id",
            ['status' => $status, 'oid' => $orderId, 'note' => $note, 'now' => date('Y-m-d H:i:s'), 'id' => $id]
        );
    }

    public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        if (!empty($filters['provider'])) {
            $where[] = '`provider` = :prov';
            $params['prov'] = $filters['provider'];
        }
        if (!empty($filters['status'])) {
            $where[] = '`status` = :st';
            $params['st'] = $filters['status'];
        }
        $clause = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `webhook_events`{$clause}", $params);
        $rows = $this->db->fetchAll(
            "SELECT * FROM `webhook_events`{$clause} ORDER BY `received_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }
}
