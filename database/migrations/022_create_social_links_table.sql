-- =====================================================================
-- Migration: 022_create_social_links_table
-- Descrição: Redes sociais gerenciáveis (substitui as chaves fixas em settings).
--            As configurações antigas em settings.social continuam válidas
--            como fallback; esta tabela é a fonte preferencial.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `social_links` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `platform`   VARCHAR(40) NOT NULL COMMENT 'discord, youtube, instagram, tiktok, x, facebook, steam...',
    `label`      VARCHAR(80) NOT NULL,
    `url`        VARCHAR(255) NOT NULL,
    `icon`       VARCHAR(40) NULL DEFAULT NULL COMMENT 'Chave do ícone',
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_social_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
