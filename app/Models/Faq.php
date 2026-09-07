<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de perguntas frequentes.
 */
class Faq extends Model
{
    protected string $table = 'faqs';
    protected array $fillable = ['category_id', 'question', 'answer', 'is_active', 'sort_order'];

    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE `question` LIKE :s";
            $params['s'] = '%' . $search . '%';
        }
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `faqs` {$where}", $params);
        $items = $this->db->fetchAll(
            "SELECT f.*, c.name AS category_name
             FROM faqs f LEFT JOIN faq_categories c ON c.id = f.category_id
             {$where} ORDER BY f.sort_order ASC, f.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['items' => $items, 'total' => $total];
    }

    /**
     * FAQs ativas agrupadas por categoria (para o site público).
     */
    public function activeGrouped(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT f.*, c.name AS category_name, c.sort_order AS cat_order
             FROM faqs f LEFT JOIN faq_categories c ON c.id = f.category_id
             WHERE f.is_active = 1
             ORDER BY cat_order ASC, f.sort_order ASC, f.id ASC"
        );
        $groups = [];
        foreach ($rows as $row) {
            $key = $row['category_name'] ?? 'Geral';
            $groups[$key][] = $row;
        }
        return $groups;
    }
}
