-- =====================================================================
-- Seed: 004_pages
-- Descrição: Páginas essenciais do sistema (administráveis, mas não removíveis).
-- =====================================================================

INSERT INTO `pages` (`title`, `slug`, `content`, `status`, `is_system`, `template`, `published_at`) VALUES
    ('Sobre o Jogo', 'sobre',
        '<p>Descreva aqui o conceito, o universo e a proposta do jogo. Este conteúdo é editável no painel administrativo.</p>',
        'published', 1, 'default', CURRENT_TIMESTAMP),
    ('Gameplay', 'gameplay',
        '<p>Apresente aqui as mecânicas, sistemas e modos de jogo. Este conteúdo é editável no painel administrativo.</p>',
        'published', 1, 'default', CURRENT_TIMESTAMP),
    ('Política de Privacidade', 'politica-de-privacidade',
        '<p>Insira aqui a política de privacidade. Este conteúdo é editável no painel administrativo.</p>',
        'published', 1, 'default', CURRENT_TIMESTAMP),
    ('Termos de Uso', 'termos-de-uso',
        '<p>Insira aqui os termos de uso. Este conteúdo é editável no painel administrativo.</p>',
        'published', 1, 'default', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
