-- =====================================================================
-- Migration: 020_create_media_table
-- Descrição: Biblioteca de mídia (arquivos enviados, reutilizáveis).
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `media` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename`    VARCHAR(255) NOT NULL COMMENT 'Nome interno seguro do arquivo original',
    `original_name` VARCHAR(255) NULL DEFAULT NULL,
    `path`        VARCHAR(255) NOT NULL COMMENT 'Caminho relativo a public/uploads',
    `mime_type`   VARCHAR(100) NULL DEFAULT NULL,
    `extension`   VARCHAR(12) NULL DEFAULT NULL,
    `size`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bytes',
    `width`       INT UNSIGNED NULL DEFAULT NULL,
    `height`      INT UNSIGNED NULL DEFAULT NULL,
    `alt_text`    VARCHAR(255) NULL DEFAULT NULL,
    `title`       VARCHAR(255) NULL DEFAULT NULL,
    `variants`    LONGTEXT NULL DEFAULT NULL COMMENT 'JSON: {thumbnail, medium, large, webp}',
    `uploaded_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_media_path` (`path`),
    KEY `idx_media_mime` (`mime_type`),
    KEY `idx_media_created` (`created_at`),
    KEY `idx_media_uploader` (`uploaded_by`),
    CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploaded_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
