-- =====================================================================
-- Migration: 001_create_migrations_table
-- Descrição: Tabela de controle do sistema de migrations.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `migrations` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration`  VARCHAR(255) NOT NULL COMMENT 'Nome do arquivo da migration',
    `batch`      INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Lote de execução',
    `status`     ENUM('success','failed') NOT NULL DEFAULT 'success',
    `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_migrations_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
