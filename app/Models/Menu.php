<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de menus.
 */
class Menu extends Model
{
    protected string $table = 'menus';
    protected array $fillable = ['name', 'location'];

    public function findByLocation(string $location): ?array
    {
        return $this->findBy('location', $location);
    }

    /**
     * Itens de um menu por localização, ordenados (para o site público).
     */
    public function itemsByLocation(string $location): array
    {
        // Menus mudam pouco: cache curto reduz o JOIN em toda página do site.
        return \App\Services\CacheService::remember('menu:' . $location, 300, function () use ($location) {
            return $this->db->fetchAll(
                "SELECT mi.* FROM menu_items mi
                 INNER JOIN menus m ON m.id = mi.menu_id
                 WHERE m.location = :loc AND mi.is_active = 1
                 ORDER BY mi.parent_id IS NULL DESC, mi.sort_order ASC, mi.id ASC",
                ['loc' => $location]
            );
        });
    }

    /**
     * Invalida o cache de itens de menu (chamar após editar menus/itens).
     */
    public static function flushCache(): void
    {
        foreach (['header', 'footer'] as $loc) {
            \App\Services\CacheService::forget('menu:' . $loc);
        }
    }
}
