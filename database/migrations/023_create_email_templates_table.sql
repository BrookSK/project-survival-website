-- =====================================================================
-- Migration: 023_create_email_templates_table
-- Descrição: Templates de e-mail administráveis com placeholders.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `email_templates` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`          VARCHAR(60) NOT NULL COMMENT 'contact_received, password_reset, smtp_test, admin_notification',
    `name`         VARCHAR(120) NOT NULL,
    `subject`      VARCHAR(255) NOT NULL,
    `body`         LONGTEXT NOT NULL COMMENT 'HTML com placeholders {{campo}}',
    `placeholders` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Lista de placeholders disponíveis (documentação)',
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email_templates_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
