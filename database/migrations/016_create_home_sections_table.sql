-- =====================================================================
-- Migration: 016_create_home_sections_table
-- Descrição: Seções editáveis da Home (conteúdo modular administrável).
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `home_sections` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`        VARCHAR(60) NOT NULL COMMENT 'Identificador da seção: about, features, gameplay...',
    `type`       VARCHAR(40) NOT NULL DEFAULT 'content' COMMENT 'content, features, screenshots, trailer, news, faq, cta',
    `title`      VARCHAR(200) NULL DEFAULT NULL,
    `subtitle`   VARCHAR(255) NULL DEFAULT NULL,
    `content`    LONGTEXT NULL DEFAULT NULL,
    `image`      VARCHAR(255) NULL DEFAULT NULL,
    `button_label` VARCHAR(80) NULL DEFAULT NULL,
    `button_url`   VARCHAR(255) NULL DEFAULT NULL,
    `extra`      LONGTEXT NULL DEFAULT NULL COMMENT 'JSON com dados adicionais específicos do tipo',
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_home_sections_key` (`key`),
    KEY `idx_home_sections_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
