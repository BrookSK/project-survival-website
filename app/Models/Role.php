<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de perfis (roles) do RBAC.
 */
class Role extends Model
{
    protected string $table = 'roles';
    protected array $fillable = ['name', 'slug', 'description', 'is_system'];

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * IDs de permissões associadas à role.
     */
    public function permissionIds(int $roleId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT `permission_id` FROM `role_permissions` WHERE `role_id` = :id",
            ['id' => $roleId]
        );
        return array_map('intval', array_column($rows, 'permission_id'));
    }

    /**
     * Substitui as permissões da role.
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->execute("DELETE FROM `role_permissions` WHERE `role_id` = :id", ['id' => $roleId]);
        foreach (array_unique(array_map('intval', $permissionIds)) as $pid) {
            if ($pid > 0) {
                $this->db->execute(
                    "INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:r, :p)",
                    ['r' => $roleId, 'p' => $pid]
                );
            }
        }
    }

    /**
     * Roles com a contagem de usuários associados.
     */
    public function allWithCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) AS users_count
             FROM roles r ORDER BY r.id ASC"
        );
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `roles` WHERE `slug` = :slug";
        $params = ['slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }
}
