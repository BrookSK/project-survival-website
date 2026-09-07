<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

/**
 * Serviço de auditoria.
 *
 * Registra ações administrativas relevantes na tabela `audit_logs`.
 * Falhas de gravação nunca interrompem o fluxo principal (apenas logam).
 */
class AuditService
{
    /**
     * Registra uma ação de auditoria.
     *
     * @param string $action  create|update|delete|login|logout|...
     * @param string $module  news|pages|users|settings|...
     */
    public static function log(
        string $action,
        string $module,
        ?string $recordId = null,
        ?string $description = null
    ): void {
        try {
            $user = AuthService::user();
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

            Database::getInstance()->execute(
                "INSERT INTO `audit_logs`
                    (`user_id`, `user_name`, `action`, `module`, `record_id`, `description`, `ip_address`, `user_agent`)
                 VALUES (:uid, :uname, :action, :module, :rid, :desc, :ip, :ua)",
                [
                    'uid'    => $user['id'] ?? null,
                    'uname'  => $user['name'] ?? null,
                    'action' => $action,
                    'module' => $module,
                    'rid'    => $recordId,
                    'desc'   => $description,
                    'ip'     => $ip,
                    'ua'     => $ua,
                ]
            );
        } catch (\Throwable $e) {
            Logger::error('Falha ao registrar auditoria: ' . $e->getMessage());
        }
    }

    /**
     * Lista paginada de logs de auditoria com filtros opcionais.
     */
    public static function paginate(int $page, int $perPage, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];

        if (!empty($filters['module'])) {
            $where[] = "`module` = :module";
            $params['module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $where[] = "`action` = :action";
            $params['action'] = $filters['action'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::getInstance();

        $total = (int) $db->fetchColumn("SELECT COUNT(*) FROM `audit_logs` {$whereSql}", $params);
        $items = $db->fetchAll(
            "SELECT * FROM `audit_logs` {$whereSql} ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total];
    }
}
