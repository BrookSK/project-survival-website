-- =====================================================================
-- Migration: 024_create_admin_notifications_table
-- Descrição: Notificações administrativas exibidas no painel.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `admin_notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`       VARCHAR(40) NOT NULL DEFAULT 'info' COMMENT 'info, success, warning, error, message',
    `title`      VARCHAR(200) NOT NULL,
    `message`    VARCHAR(500) NULL DEFAULT NULL,
    `url`        VARCHAR(255) NULL DEFAULT NULL COMMENT 'Link de destino ao clicar',
    `icon`       VARCHAR(40) NULL DEFAULT NULL,
    `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_notif_read` (`is_read`),
    KEY `idx_admin_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
