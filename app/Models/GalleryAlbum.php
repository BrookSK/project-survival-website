<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de álbuns da galeria.
 */
class GalleryAlbum extends Model
{
    protected string $table = 'gallery_albums';
    protected array $fillable = ['title', 'slug', 'description', 'cover_image', 'is_active', 'sort_order'];

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `gallery_albums` WHERE `slug` = :slug";
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
            "SELECT a.*, (SELECT COUNT(*) FROM gallery_items i WHERE i.album_id = a.id) AS items_count
             FROM gallery_albums a ORDER BY a.sort_order ASC, a.id DESC"
        );
    }

    public function activeWithCover(): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, (SELECT COUNT(*) FROM gallery_items i WHERE i.album_id = a.id AND i.is_active = 1) AS items_count
             FROM gallery_albums a WHERE a.is_active = 1 ORDER BY a.sort_order ASC, a.id DESC"
        );
    }

    public function findActiveBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `gallery_albums` WHERE `slug` = :slug AND `is_active` = 1 LIMIT 1",
            ['slug' => $slug]
        );
    }
}
