<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de notícias.
 *
 * Suporta soft delete (deleted_at) e publicação agendada (status='scheduled'
 * com published_at futuro). "Publicada de fato" = status='published' e
 * published_at <= NOW().
 */
class News extends Model
{
    protected string $table = 'news';
    protected bool $softDeletes = true;
    protected array $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'status', 'is_featured', 'author_id', 'views', 'seo_title', 'seo_description',
        'og_image', 'published_at',
    ];

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `news` WHERE `slug` = :slug";
        $params = ['slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Listagem administrativa (exclui soft-deleted) com busca/filtro/ordenação.
     *
     * @param string $sort  Campo permitido: published_at|title|views|status
     * @param string $dir   asc|desc
     */
    public function paginate(int $page, int $perPage, string $search = '', ?string $status = null, string $sort = 'published_at', string $dir = 'desc'): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = ['`deleted_at` IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = "`title` LIKE :s";
            $params['s'] = '%' . $search . '%';
        }
        if ($status !== null && $status !== '') {
            $where[] = "`status` = :status";
            $params['status'] = $status;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // Whitelist de ordenação (nunca aceita coluna arbitrária da requisição)
        $sortMap = [
            'published_at' => 'COALESCE(`published_at`, `created_at`)',
            'title'        => '`title`',
            'views'        => '`views`',
            'status'       => '`status`',
        ];
        $orderCol = $sortMap[$sort] ?? $sortMap['published_at'];
        $orderDir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `news` {$whereSql}", $params);
        $items = $this->db->fetchAll(
            "SELECT * FROM `news` {$whereSql} ORDER BY {$orderCol} {$orderDir} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['items' => $items, 'total' => $total];
    }

    /**
     * Notícias publicadas (para o site público), paginadas.
     */
    public function published(int $page, int $perPage, ?string $categorySlug = null): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $join = '';
        $extra = '';

        if ($categorySlug) {
            $join = "INNER JOIN news_category_relations ncr ON ncr.news_id = n.id
                     INNER JOIN news_categories c ON c.id = ncr.category_id";
            $extra = "AND c.slug = :cat";
            $params['cat'] = $categorySlug;
        }

        $publishedCond = "n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL";

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT n.id) FROM news n {$join} WHERE {$publishedCond} {$extra}",
            $params
        );

        $items = $this->db->fetchAll(
            "SELECT DISTINCT n.* FROM news n {$join}
             WHERE {$publishedCond} {$extra}
             ORDER BY n.published_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total];
    }

    public function latest(int $limit = 3): array
    {
        $limit = max(1, (int) $limit);
        return $this->db->fetchAll(
            "SELECT * FROM `news`
             WHERE `status` = 'published' AND `published_at` <= NOW() AND `deleted_at` IS NULL
             ORDER BY `published_at` DESC LIMIT {$limit}"
        );
    }

    /**
     * Notícias em destaque publicadas (para a home).
     */
    public function featured(int $limit = 3): array
    {
        $limit = max(1, (int) $limit);
        return $this->db->fetchAll(
            "SELECT * FROM `news`
             WHERE `status` = 'published' AND `published_at` <= NOW() AND `deleted_at` IS NULL
               AND `is_featured` = 1
             ORDER BY `published_at` DESC LIMIT {$limit}"
        );
    }

    /**
     * Conta quantas notícias estão marcadas como destaque (para o limite).
     */
    public function featuredCount(?int $excludeId = null): int
    {
        $sql = "SELECT COUNT(*) FROM `news` WHERE `is_featured` = 1 AND `deleted_at` IS NULL";
        $params = [];
        if ($excludeId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $excludeId;
        }
        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `news`
             WHERE `slug` = :slug AND `status` = 'published' AND `published_at` <= NOW() AND `deleted_at` IS NULL
             LIMIT 1",
            ['slug' => $slug]
        );
    }

    public function incrementViews(int $id): void
    {
        $this->db->execute("UPDATE `news` SET `views` = `views` + 1 WHERE `id` = :id", ['id' => $id]);
    }

    /**
     * Publica notícias agendadas cuja data já chegou (cron/manutenção).
     *
     * @return int Quantidade publicada.
     */
    public function publishDueScheduled(): int
    {
        return $this->db->execute(
            "UPDATE `news` SET `status` = 'published'
             WHERE `status` = 'scheduled' AND `published_at` <= NOW() AND `deleted_at` IS NULL"
        );
    }

    // ---- Categorias (N:N) ----

    public function categoryIds(int $newsId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT `category_id` FROM `news_category_relations` WHERE `news_id` = :id",
            ['id' => $newsId]
        );
        return array_map('intval', array_column($rows, 'category_id'));
    }

    public function categories(int $newsId): array
    {
        return $this->db->fetchAll(
            "SELECT c.* FROM news_categories c
             INNER JOIN news_category_relations ncr ON ncr.category_id = c.id
             WHERE ncr.news_id = :id",
            ['id' => $newsId]
        );
    }

    public function syncCategories(int $newsId, array $categoryIds): void
    {
        $this->db->execute("DELETE FROM `news_category_relations` WHERE `news_id` = :id", ['id' => $newsId]);
        foreach (array_unique(array_map('intval', $categoryIds)) as $cid) {
            if ($cid > 0) {
                $this->db->execute(
                    "INSERT INTO `news_category_relations` (`news_id`, `category_id`) VALUES (:n, :c)",
                    ['n' => $newsId, 'c' => $cid]
                );
            }
        }
    }
}
