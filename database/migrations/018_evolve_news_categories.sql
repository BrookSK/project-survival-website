-- =====================================================================
-- Migration: 018_evolve_news_categories
-- Descrição: Adiciona imagem e status às categorias de notícias.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

ALTER TABLE `news_categories`
    ADD COLUMN `image` VARCHAR(255) NULL DEFAULT NULL AFTER `description`,
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `image`;

ALTER TABLE `news_categories`
    ADD KEY `idx_news_categories_active` (`is_active`);
