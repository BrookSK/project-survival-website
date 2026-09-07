-- =====================================================================
-- Migration: 002_create_users_table
-- Descrição: Usuários administrativos do sistema.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(150) NOT NULL,
    `email`            VARCHAR(190) NOT NULL,
    `password`         VARCHAR(255) NOT NULL COMMENT 'Hash gerado por password_hash()',
    `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at`    TIMESTAMP NULL DEFAULT NULL,
    `last_login_ip`    VARCHAR(45) NULL DEFAULT NULL,
    `remember_token`   VARCHAR(100) NULL DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
