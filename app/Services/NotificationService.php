<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

/**
 * Notificações administrativas exibidas no painel.
 *
 * Grava em `admin_notifications`. Falhas nunca interrompem o fluxo principal.
 */
class NotificationService
{
    /**
     * Cria uma notificação.
     */
    public static function push(string $type, string $title, ?string $message = null, ?string $url = null, ?string $icon = null): void
    {
        try {
            Database::getInstance()->execute(
                "INSERT INTO `admin_notifications` (`type`, `title`, `message`, `url`, `icon`)
                 VALUES (:type, :title, :message, :url, :icon)",
                [
                    'type'    => $type,
                    'title'   => $title,
                    'message' => $message !== null ? mb_substr_safe($message, 500) : null,
                    'url'     => $url,
                    'icon'    => $icon,
                ]
            );
        } catch (\Throwable $e) {
            Logger::error('Falha ao criar notificação: ' . $e->getMessage());
        }
    }

    public static function unreadCount(): int
    {
        try {
            return (int) Database::getInstance()->fetchColumn(
                "SELECT COUNT(*) FROM `admin_notifications` WHERE `is_read` = 0"
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function recent(int $limit = 10): array
    {
        try {
            $limit = max(1, (int) $limit);
            return Database::getInstance()->fetchAll(
                "SELECT * FROM `admin_notifications` ORDER BY `created_at` DESC LIMIT {$limit}"
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function markAllRead(): void
    {
        try {
            Database::getInstance()->execute("UPDATE `admin_notifications` SET `is_read` = 1 WHERE `is_read` = 0");
        } catch (\Throwable $e) {
            Logger::error('Falha ao marcar notificações como lidas: ' . $e->getMessage());
        }
    }

    public static function markRead(int $id): void
    {
        try {
            Database::getInstance()->execute(
                "UPDATE `admin_notifications` SET `is_read` = 1 WHERE `id` = :id",
                ['id' => $id]
            );
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    /**
     * Limpa notificações antigas já lidas (manutenção).
     */
    public static function purgeOld(int $days = 30): int
    {
        try {
            return Database::getInstance()->execute(
                "DELETE FROM `admin_notifications` WHERE `is_read` = 1 AND `created_at` < (NOW() - INTERVAL :d DAY)",
                ['d' => $days]
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

/**
 * Substring segura com fallback sem mbstring.
 */
if (!function_exists('App\\Services\\mb_substr_safe')) {
    function mb_substr_safe(string $s, int $len): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $len);
        }
        return substr($s, 0, $len);
    }
}
