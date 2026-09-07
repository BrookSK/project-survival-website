<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de itens da galeria (imagens e vídeos).
 */
class GalleryItem extends Model
{
    protected string $table = 'gallery_items';
    protected array $fillable = [
        'album_id', 'type', 'title', 'file_path', 'video_url',
        'alt_text', 'is_active', 'sort_order',
    ];

    public function byAlbum(int $albumId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `gallery_items` WHERE `album_id` = :id ORDER BY `sort_order` ASC, `id` ASC",
            ['id' => $albumId]
        );
    }

    public function activeByAlbum(int $albumId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `gallery_items` WHERE `album_id` = :id AND `is_active` = 1
             ORDER BY `sort_order` ASC, `id` ASC",
            ['id' => $albumId]
        );
    }
}
