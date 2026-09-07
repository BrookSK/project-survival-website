<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de usuários administrativos.
 */
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'name', 'email', 'password', 'is_active',
        'last_login_at', 'last_login_ip', 'remember_token',
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Verifica se um e-mail já existe (opcionalmente ignorando um ID).
     */
    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `users` WHERE `email` = :email";
        $params = ['email' => $email];
        if ($ignoreId !== null) {
            $sql .= " AND `id` <> :id";
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Lista paginada com busca por nome/e-mail.
     */
    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = "WHERE `name` LIKE :s OR `email` LIKE :s";
            $params['s'] = '%' . $search . '%';
        }

        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `users` {$where}", $params);

        $rows = $this->db->fetchAll(
            "SELECT `id`, `name`, `email`, `is_active`, `last_login_at`, `created_at`
             FROM `users` {$where} ORDER BY `name` ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $rows, 'total' => $total];
    }

    /**
     * Retorna os IDs das roles do usuário.
     */
    public function roleIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT `role_id` FROM `user_roles` WHERE `user_id` = :id",
            ['id' => $userId]
        );
        return array_map('intval', array_column($rows, 'role_id'));
    }

    /**
     * Retorna as roles (nome/slug) do usuário.
     */
    public function roles(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT r.id, r.name, r.slug
             FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :id",
            ['id' => $userId]
        );
    }

    /**
     * Substitui as roles do usuário.
     */
    public function syncRoles(int $userId, array $roleIds): void
    {
        $this->db->execute("DELETE FROM `user_roles` WHERE `user_id` = :id", ['id' => $userId]);
        foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
            if ($roleId > 0) {
                $this->db->execute(
                    "INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (:u, :r)",
                    ['u' => $userId, 'r' => $roleId]
                );
            }
        }
    }

    /**
     * Todas as permissões (slugs) do usuário via suas roles.
     */
    public function permissionSlugs(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT p.slug
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :id",
            ['id' => $userId]
        );
        return array_column($rows, 'slug');
    }

    /**
     * Verifica se o usuário possui alguma role pelo slug.
     */
    public function hasRole(int $userId, string $slug): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :id AND r.slug = :slug",
            ['id' => $userId, 'slug' => $slug]
        ) > 0;
    }

    public function updateLastLogin(int $userId, string $ip): void
    {
        $this->db->execute(
            "UPDATE `users` SET `last_login_at` = NOW(), `last_login_ip` = :ip WHERE `id` = :id",
            ['ip' => $ip, 'id' => $userId]
        );
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $this->db->execute(
            "UPDATE `users` SET `password` = :p WHERE `id` = :id",
            ['p' => $hash, 'id' => $userId]
        );
    }
}
