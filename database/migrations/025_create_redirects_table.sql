-- =====================================================================
-- Migration: 025_create_redirects_table
-- Descrição: Redirecionamentos administráveis (SEO / mudanças de URL).
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `redirects` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_path`  VARCHAR(255) NOT NULL COMMENT 'Caminho antigo (ex.: /noticia-antiga)',
    `to_url`     VARCHAR(255) NOT NULL COMMENT 'Destino (path interno ou URL absoluta)',
    `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    `hits`       INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_redirects_from` (`from_path`),
    KEY `idx_redirects_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
