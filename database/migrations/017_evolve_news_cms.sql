-- =====================================================================
-- Migration: 017_evolve_news_cms
-- Descrição: Evolui as notícias para um CMS completo:
--   - status 'scheduled' (publicação agendada)
--   - is_featured (destaque na home)
--   - deleted_at (soft delete)
--   - índices para busca/ordenação/destaque
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

ALTER TABLE `news`
    MODIFY COLUMN `status` ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft';

ALTER TABLE `news`
    ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
    ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

ALTER TABLE `news`
    ADD KEY `idx_news_featured` (`is_featured`),
    ADD KEY `idx_news_deleted` (`deleted_at`);
