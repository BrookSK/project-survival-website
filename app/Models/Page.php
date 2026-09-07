<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de páginas de conteúdo.
 */
class Page extends Model
{
    protected string $table = 'pages';
    protected array $fillable = [
        'title', 'slug', 'content', 'status', 'is_system',
        'template', 'author_id', 'published_at',
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `pages` WHERE `slug` = :slug AND `status` = 'published' LIMIT 1",
            ['slug' => $slug]
        );
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `pages` WHERE `slug` = :slug";
        $params = ['slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE `title` LIKE :s OR `slug` LIKE :s";
            $params['s'] = '%' . $search . '%';
        }
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `pages` {$where}", $params);
        $items = $this->db->fetchAll(
            "SELECT * FROM `pages` {$where} ORDER BY `updated_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['items' => $items, 'total' => $total];
    }

    // ---- SEO meta ----

    public function meta(int $pageId): ?array
    {
        return $this->db->fetch("SELECT * FROM `page_meta` WHERE `page_id` = :id", ['id' => $pageId]);
    }

    public function saveMeta(int $pageId, array $meta): void
    {
        $existing = $this->meta($pageId);
        $fields = ['seo_title', 'seo_description', 'seo_keywords', 'canonical_url', 'og_image', 'robots'];
        $data = ['page_id' => $pageId];
        foreach ($fields as $f) {
            $data[$f] = $meta[$f] ?? null;
        }
        $data['robots'] = $data['robots'] ?: 'index,follow';

        if ($existing) {
            $this->db->execute(
                "UPDATE `page_meta` SET `seo_title`=:seo_title, `seo_description`=:seo_description,
                    `seo_keywords`=:seo_keywords, `canonical_url`=:canonical_url, `og_image`=:og_image,
                    `robots`=:robots WHERE `page_id`=:page_id",
                $data
            );
        } else {
            $this->db->execute(
                "INSERT INTO `page_meta`
                    (`page_id`, `seo_title`, `seo_description`, `seo_keywords`, `canonical_url`, `og_image`, `robots`)
                 VALUES (:page_id, :seo_title, :seo_description, :seo_keywords, :canonical_url, :og_image, :robots)",
                $data
            );
        }
    }
}
