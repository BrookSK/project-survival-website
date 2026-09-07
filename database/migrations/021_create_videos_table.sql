-- =====================================================================
-- Migration: 021_create_videos_table
-- Descrição: Vídeos/Trailers (YouTube, Vimeo ou URL externa).
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `videos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(200) NOT NULL,
    `provider`    ENUM('youtube','vimeo','external') NOT NULL DEFAULT 'youtube',
    `url`         VARCHAR(500) NOT NULL,
    `video_id`    VARCHAR(120) NULL DEFAULT NULL COMMENT 'ID extraído (YouTube/Vimeo)',
    `thumbnail`   VARCHAR(255) NULL DEFAULT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Trailer principal',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_videos_active_order` (`is_active`, `sort_order`),
    KEY `idx_videos_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
