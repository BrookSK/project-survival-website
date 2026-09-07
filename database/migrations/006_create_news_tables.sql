-- =====================================================================
-- Migration: 006_create_news_tables
-- Descrição: Sistema de notícias/blog: categorias, notícias e relação N:N.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `news_categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120) NOT NULL,
    `slug`        VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_news_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            VARCHAR(200) NOT NULL,
    `slug`             VARCHAR(200) NOT NULL,
    `excerpt`          VARCHAR(500) NULL DEFAULT NULL,
    `content`          LONGTEXT NULL DEFAULT NULL,
    `featured_image`   VARCHAR(255) NULL DEFAULT NULL,
    `status`           ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `author_id`        INT UNSIGNED NULL DEFAULT NULL,
    `views`            INT UNSIGNED NOT NULL DEFAULT 0,
    `seo_title`        VARCHAR(200) NULL DEFAULT NULL,
    `seo_description`  VARCHAR(300) NULL DEFAULT NULL,
    `og_image`         VARCHAR(255) NULL DEFAULT NULL,
    `published_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_news_slug` (`slug`),
    KEY `idx_news_status` (`status`),
    KEY `idx_news_published_at` (`published_at`),
    KEY `idx_news_author` (`author_id`),
    CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news_category_relations` (
    `news_id`     INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`news_id`, `category_id`),
    KEY `idx_ncr_category` (`category_id`),
    CONSTRAINT `fk_ncr_news` FOREIGN KEY (`news_id`)
        REFERENCES `news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ncr_category` FOREIGN KEY (`category_id`)
        REFERENCES `news_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
