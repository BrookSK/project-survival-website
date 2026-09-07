<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de permissões do RBAC.
 */
class Permission extends Model
{
    protected string $table = 'permissions';
    protected array $fillable = ['name', 'slug', 'group'];

    /**
     * Retorna todas as permissões agrupadas por 'group'.
     */
    public function grouped(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM `permissions` ORDER BY `group` ASC, `name` ASC");
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['group']][] = $row;
        }
        return $groups;
    }
}
