<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de redirecionamentos administráveis (SEO).
 */
class Redirect extends Model
{
    protected string $table = 'redirects';
    protected array $fillable = ['from_path', 'to_url', 'status_code', 'is_active'];

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `redirects` ORDER BY `from_path` ASC");
    }

    /**
     * Busca um redirect ativo para um caminho. Registra o hit.
     */
    public function matchActive(string $path): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM `redirects` WHERE `from_path` = :p AND `is_active` = 1 LIMIT 1",
            ['p' => $path]
        );
        if ($row) {
            $this->db->execute("UPDATE `redirects` SET `hits` = `hits` + 1 WHERE `id` = :id", ['id' => $row['id']]);
        }
        return $row;
    }

    public function fromExists(string $fromPath, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `redirects` WHERE `from_path` = :p";
        $params = ['p' => $fromPath];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }
}
