-- =====================================================================
-- Migration: 009_create_menus_tables
-- Descrição: Menus configuráveis (ex.: header, footer) e seus itens.
--            Suporta hierarquia via parent_id.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `menus` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `location`   VARCHAR(60) NOT NULL COMMENT 'header, footer, sidebar...',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_menus_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `menu_id`    INT UNSIGNED NOT NULL,
    `parent_id`  INT UNSIGNED NULL DEFAULT NULL,
    `label`      VARCHAR(120) NOT NULL,
    `url`        VARCHAR(255) NOT NULL,
    `target`     ENUM('_self','_blank') NOT NULL DEFAULT '_self',
    `icon`       VARCHAR(80) NULL DEFAULT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_menu_items_menu` (`menu_id`),
    KEY `idx_menu_items_parent` (`parent_id`),
    CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`menu_id`)
        REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`)
        REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
