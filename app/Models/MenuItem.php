<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de itens de menu.
 */
class MenuItem extends Model
{
    protected string $table = 'menu_items';
    protected array $fillable = [
        'menu_id', 'parent_id', 'label', 'url', 'target', 'icon', 'is_active', 'sort_order',
    ];

    public function byMenu(int $menuId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `menu_items` WHERE `menu_id` = :id ORDER BY `sort_order` ASC, `id` ASC",
            ['id' => $menuId]
        );
    }
}
