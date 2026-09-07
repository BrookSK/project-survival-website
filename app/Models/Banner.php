<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de banners/slides.
 */
class Banner extends Model
{
    protected string $table = 'banners';
    protected array $fillable = [
        'title', 'subtitle', 'image_path', 'image_mobile', 'link_url', 'link_label',
        'position', 'is_active', 'sort_order', 'starts_at', 'ends_at',
    ];

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `banners` ORDER BY `position` ASC, `sort_order` ASC, `id` DESC");
    }

    /**
     * Banners ativos e dentro do período de exibição, para uma posição.
     */
    public function activeByPosition(string $position): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `banners`
             WHERE `position` = :pos AND `is_active` = 1
               AND (`starts_at` IS NULL OR `starts_at` <= NOW())
               AND (`ends_at` IS NULL OR `ends_at` >= NOW())
             ORDER BY `sort_order` ASC, `id` DESC",
            ['pos' => $position]
        );
    }
}
