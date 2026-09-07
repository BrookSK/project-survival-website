-- =====================================================================
-- Migration: 008_create_gallery_tables
-- Descrição: Galeria de mídia organizada em álbuns.
--            Preparada para imagens e vídeos (campo type).
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `gallery_albums` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(150) NOT NULL,
    `slug`        VARCHAR(150) NOT NULL,
    `description` VARCHAR(500) NULL DEFAULT NULL,
    `cover_image` VARCHAR(255) NULL DEFAULT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_gallery_albums_slug` (`slug`),
    KEY `idx_gallery_albums_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_items` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `album_id`    INT UNSIGNED NULL DEFAULT NULL,
    `type`        ENUM('image','video') NOT NULL DEFAULT 'image',
    `title`       VARCHAR(150) NULL DEFAULT NULL,
    `file_path`   VARCHAR(255) NULL DEFAULT NULL COMMENT 'Caminho da imagem (type=image)',
    `video_url`   VARCHAR(255) NULL DEFAULT NULL COMMENT 'URL do vídeo (type=video)',
    `alt_text`    VARCHAR(255) NULL DEFAULT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gallery_items_album` (`album_id`),
    KEY `idx_gallery_items_active` (`is_active`),
    CONSTRAINT `fk_gallery_items_album` FOREIGN KEY (`album_id`)
        REFERENCES `gallery_albums` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
