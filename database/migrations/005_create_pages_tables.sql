-- =====================================================================
-- Migration: 005_create_pages_tables
-- Descrição: Páginas de conteúdo (sobre, gameplay, privacidade, termos, etc.)
--            e seus metadados de SEO.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `pages` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(200) NOT NULL,
    `slug`         VARCHAR(200) NOT NULL,
    `content`      LONGTEXT NULL DEFAULT NULL,
    `status`       ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `is_system`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = página essencial, não removível',
    `template`     VARCHAR(80) NOT NULL DEFAULT 'default',
    `author_id`    INT UNSIGNED NULL DEFAULT NULL,
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pages_slug` (`slug`),
    KEY `idx_pages_status` (`status`),
    KEY `idx_pages_author` (`author_id`),
    CONSTRAINT `fk_pages_author` FOREIGN KEY (`author_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `page_meta` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id`          INT UNSIGNED NOT NULL,
    `seo_title`        VARCHAR(200) NULL DEFAULT NULL,
    `seo_description`  VARCHAR(300) NULL DEFAULT NULL,
    `seo_keywords`     VARCHAR(255) NULL DEFAULT NULL,
    `canonical_url`    VARCHAR(255) NULL DEFAULT NULL,
    `og_image`         VARCHAR(255) NULL DEFAULT NULL,
    `robots`           VARCHAR(80) NOT NULL DEFAULT 'index,follow',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_page_meta_page` (`page_id`),
    CONSTRAINT `fk_page_meta_page` FOREIGN KEY (`page_id`)
        REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
