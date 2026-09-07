<?php

namespace App\Models;

use App\Core\Model;

/**
 * Solicitações de titulares de dados (acesso, correção, exclusão, etc.).
 */
class PrivacyRequest extends Model
{
    protected string $table = 'privacy_requests';
    protected array $fillable = [
        'public_id', 'player_id', 'email', 'type', 'status',
        'message', 'admin_note', 'handled_by', 'ip_address', 'completed_at',
    ];

    public const TYPES = ['access', 'correction', 'deletion', 'portability', 'consent_revocation', 'information', 'other'];
    public const STATUSES = ['pending', 'in_review', 'awaiting_user', 'completed', 'rejected', 'cancelled'];

    /**
     * Abre uma solicitação com identificador público aleatório.
     */
    public function open(string $type, ?string $playerId, ?string $email, ?string $message, ?string $ip): array
    {
        $publicId = 'req_' . bin2hex(random_bytes(8));
        $id = $this->create([
            'public_id' => $publicId,
            'player_id' => $playerId,
            'email'     => $email,
            'type'      => in_array($type, self::TYPES, true) ? $type : 'other',
            'status'    => 'pending',
            'message'   => $message,
            'ip_address'=> $ip,
        ]);
        return ['id' => $id, 'public_id' => $publicId];
    }

    public function findByPublicId(string $publicId): ?array
    {
        return $this->findBy('public_id', $publicId);
    }

    /**
     * Lista paginada com filtros opcionais (status, type).
     */
    public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = '`status` = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['type']) && in_array($filters['type'], self::TYPES, true)) {
            $where[] = '`type` = :type';
            $params['type'] = $filters['type'];
        }
        $clause = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `privacy_requests`{$clause}", $params);
        $rows = $this->db->fetchAll(
            "SELECT * FROM `privacy_requests`{$clause} ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Atualiza status e nota interna, registrando o responsável.
     */
    public function setStatus(int $id, string $status, int $handledBy, ?string $note = null): int
    {
        if (!in_array($status, self::STATUSES, true)) {
            return 0;
        }
        $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
        return $this->db->execute(
            "UPDATE `privacy_requests`
             SET `status` = :status, `handled_by` = :uid, `admin_note` = COALESCE(:note, `admin_note`),
                 `completed_at` = :completedAt
             WHERE `id` = :id",
            ['status' => $status, 'uid' => $handledBy, 'note' => $note, 'completedAt' => $completedAt, 'id' => $id]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM `privacy_requests` WHERE `status` = :s",
            ['s' => $status]
        );
    }

    /**
     * Solicitações de um jogador (campos públicos, para exibição na conta).
     */
    public function forPlayer(string $playerId): array
    {
        return $this->db->fetchAll(
            "SELECT `public_id`, `type`, `status`, `created_at`, `completed_at`
             FROM `privacy_requests` WHERE `player_id` = :pid ORDER BY `created_at` DESC",
            ['pid' => $playerId]
        );
    }
}
