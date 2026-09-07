-- =====================================================================
-- Migration: 012_create_audit_logs_table
-- Descrição: Registro de ações administrativas para auditoria.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NULL DEFAULT NULL,
    `user_name`   VARCHAR(150) NULL DEFAULT NULL COMMENT 'Snapshot do nome (mantido mesmo se o usuário for excluído)',
    `action`      VARCHAR(60) NOT NULL COMMENT 'create, update, delete, login, logout...',
    `module`      VARCHAR(80) NOT NULL COMMENT 'news, pages, users, settings...',
    `record_id`   VARCHAR(80) NULL DEFAULT NULL COMMENT 'ID do registro afetado',
    `description` VARCHAR(500) NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45) NULL DEFAULT NULL,
    `user_agent`  VARCHAR(255) NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_module` (`module`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
