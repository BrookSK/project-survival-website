-- =====================================================================
-- Migration: 015_add_image_mobile_to_banners
-- Descrição: Adiciona imagem específica para mobile aos banners.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

ALTER TABLE `banners`
    ADD COLUMN `image_mobile` VARCHAR(255) NULL DEFAULT NULL AFTER `image_path`;
