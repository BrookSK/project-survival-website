-- =====================================================================
-- Seed: 010_legal_pages
-- Descrição: Página de Política de Cookies (as demais já existem no seed 004).
-- Idempotente por slug.
-- =====================================================================

INSERT INTO `pages` (`title`, `slug`, `content`, `status`, `is_system`, `template`, `published_at`) VALUES
    ('Política de Cookies', 'politica-de-cookies',
        '<p>Descreva aqui como o site utiliza cookies. Este conteúdo é editável no painel administrativo.</p>',
        'published', 1, 'default', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
