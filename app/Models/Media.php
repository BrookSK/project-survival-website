<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model da biblioteca de mídia.
 */
class Media extends Model
{
    protected string $table = 'media';
    protected array $fillable = [
        'filename', 'original_name', 'path', 'mime_type', 'extension',
        'size', 'width', 'height', 'alt_text', 'title', 'variants', 'uploaded_by',
    ];

    /**
     * Lista paginada com busca por nome/título.
     */
    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE `original_name` LIKE :s OR `title` LIKE :s OR `alt_text` LIKE :s";
            $params['s'] = '%' . $search . '%';
        }
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `media` {$where}", $params);
        $items = $this->db->fetchAll(
            "SELECT * FROM `media` {$where} ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['items' => $items, 'total' => $total];
    }

    public function updateMeta(int $id, string $title, string $alt): int
    {
        return $this->db->execute(
            "UPDATE `media` SET `title` = :t, `alt_text` = :a WHERE `id` = :id",
            ['t' => $title, 'a' => $alt, 'id' => $id]
        );
    }

    /**
     * Retorna o caminho de uma variante específica (ex.: 'thumbnail'),
     * com fallback para o original.
     */
    public static function variant(array $media, string $size = 'medium'): string
    {
        if (!empty($media['variants'])) {
            $variants = is_array($media['variants']) ? $media['variants'] : (json_decode($media['variants'], true) ?: []);
            if (!empty($variants[$size])) {
                return $variants[$size];
            }
        }
        return $media['path'] ?? '';
    }
}
