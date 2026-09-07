<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de mensagens de contato.
 */
class ContactMessage extends Model
{
    protected string $table = 'contact_messages';
    protected array $fillable = [
        'name', 'email', 'subject', 'message', 'status', 'ip_address', 'user_agent',
    ];

    public function paginate(int $page, int $perPage, string $search = '', ?string $status = null): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = "(`name` LIKE :s OR `email` LIKE :s OR `subject` LIKE :s)";
            $params['s'] = '%' . $search . '%';
        }
        if ($status !== null && $status !== '') {
            $where[] = "`status` = :status";
            $params['status'] = $status;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `contact_messages` {$whereSql}", $params);
        $items = $this->db->fetchAll(
            "SELECT * FROM `contact_messages` {$whereSql} ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['items' => $items, 'total' => $total];
    }

    public function updateStatus(int $id, string $status): void
    {
        $allowed = ['new', 'read', 'replied', 'archived'];
        if (!in_array($status, $allowed, true)) {
            return;
        }
        $this->db->execute("UPDATE `contact_messages` SET `status` = :s WHERE `id` = :id", ['s' => $status, 'id' => $id]);
    }
}
