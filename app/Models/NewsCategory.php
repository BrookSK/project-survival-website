<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de categorias de notícias.
 */
class NewsCategory extends Model
{
    protected string $table = 'news_categories';
    protected array $fillable = ['name', 'slug', 'description', 'image', 'is_active'];

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `news_categories` WHERE `slug` = :slug";
        $params = ['slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function allWithCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM news_category_relations r WHERE r.category_id = c.id) AS news_count
             FROM news_categories c ORDER BY c.name ASC"
        );
    }

    /**
     * Categorias que possuem ao menos uma notícia publicada (para o site).
     */
    public function withPublished(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT c.* FROM news_categories c
             INNER JOIN news_category_relations r ON r.category_id = c.id
             INNER JOIN news n ON n.id = r.news_id
             WHERE n.status = 'published' AND n.published_at <= NOW()
             ORDER BY c.name ASC"
        );
    }
}
