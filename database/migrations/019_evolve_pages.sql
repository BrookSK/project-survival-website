-- =====================================================================
-- Migration: 019_evolve_pages
-- Descrição: Evolui páginas: imagem destacada, status 'archived' e soft delete.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

ALTER TABLE `pages`
    MODIFY COLUMN `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft';

ALTER TABLE `pages`
    ADD COLUMN `featured_image` VARCHAR(255) NULL DEFAULT NULL AFTER `content`,
    ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

ALTER TABLE `pages`
    ADD KEY `idx_pages_deleted` (`deleted_at`);
