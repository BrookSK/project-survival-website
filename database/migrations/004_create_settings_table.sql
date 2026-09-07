-- =====================================================================
-- Migration: 004_create_settings_table
-- Descrição: Configurações administráveis do sistema (chave/valor por grupo).
--            Substitui o uso de .env para dados não sensíveis de infraestrutura.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group`       VARCHAR(60) NOT NULL DEFAULT 'general' COMMENT 'general, email, seo, social, system, notifications',
    `key`         VARCHAR(120) NOT NULL,
    `value`       LONGTEXT NULL DEFAULT NULL,
    `type`        ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
    `is_secret`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = valor sensível (ex.: senha SMTP)',
    `label`       VARCHAR(150) NULL DEFAULT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_group_key` (`group`, `key`),
    KEY `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
