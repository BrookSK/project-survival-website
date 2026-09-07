<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model das seções editáveis da Home.
 */
class HomeSection extends Model
{
    protected string $table = 'home_sections';
    protected array $fillable = [
        'key', 'type', 'title', 'subtitle', 'content', 'image',
        'button_label', 'button_url', 'extra', 'is_active', 'sort_order',
    ];

    /**
     * Todas as seções ordenadas (para o admin).
     */
    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `home_sections` ORDER BY `sort_order` ASC, `id` ASC");
    }

    /**
     * Seções ativas ordenadas (para o site público).
     */
    public function activeOrdered(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `home_sections` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC"
        );
    }

    public function keyExists(string $key, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `home_sections` WHERE `key` = :key";
        $params = ['key' => $key];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Persiste a nova ordem a partir de uma lista de IDs.
     */
    public function reorder(array $orderedIds): void
    {
        $this->transaction(function () use ($orderedIds) {
            $pos = 1;
            foreach ($orderedIds as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $this->db->execute(
                        "UPDATE `home_sections` SET `sort_order` = :o WHERE `id` = :id",
                        ['o' => $pos++, 'id' => $id]
                    );
                }
            }
        });
    }
}
