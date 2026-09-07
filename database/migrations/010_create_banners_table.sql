-- =====================================================================
-- Migration: 010_create_banners_table
-- Descrição: Banners/slides gerenciáveis para áreas do site (ex.: hero).
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `banners` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(150) NULL DEFAULT NULL,
    `subtitle`    VARCHAR(255) NULL DEFAULT NULL,
    `image_path`  VARCHAR(255) NULL DEFAULT NULL,
    `link_url`    VARCHAR(255) NULL DEFAULT NULL,
    `link_label`  VARCHAR(80) NULL DEFAULT NULL,
    `position`    VARCHAR(60) NOT NULL DEFAULT 'home_hero' COMMENT 'Área onde é exibido',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `starts_at`   TIMESTAMP NULL DEFAULT NULL,
    `ends_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_banners_position` (`position`),
    KEY `idx_banners_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
