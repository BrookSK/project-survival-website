<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de redes sociais gerenciáveis.
 */
class SocialLink extends Model
{
    protected string $table = 'social_links';
    protected array $fillable = ['platform', 'label', 'url', 'icon', 'is_active', 'sort_order'];

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `social_links` ORDER BY `sort_order` ASC, `id` ASC");
    }

    public function activeOrdered(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `social_links` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC"
        );
    }
}
