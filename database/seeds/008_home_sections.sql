-- =====================================================================
-- Seed: 008_home_sections
-- Descrição: Seções padrão da Home (modular, editável no painel).
-- Idempotente por `key`.
-- =====================================================================

INSERT INTO `home_sections` (`key`, `type`, `title`, `subtitle`, `content`, `button_label`, `button_url`, `is_active`, `sort_order`) VALUES
    ('about', 'content', 'Sobre o jogo', 'Conheça o universo',
        '<p>Descreva aqui o conceito e o universo do jogo. Este conteúdo é editável no painel administrativo.</p>',
        'Saiba mais', '/sobre', 1, 1),
    ('features', 'features', 'Principais características', 'O que torna o jogo único',
        NULL, NULL, NULL, 1, 2),
    ('gameplay', 'content', 'Gameplay', 'Veja como se joga',
        '<p>Apresente aqui as mecânicas e sistemas do jogo.</p>',
        'Ver gameplay', '/gameplay', 1, 3),
    ('screenshots', 'screenshots', 'Screenshots', 'Imagens do jogo',
        NULL, NULL, NULL, 1, 4),
    ('trailer', 'trailer', 'Trailer', 'Assista ao trailer oficial',
        NULL, NULL, NULL, 1, 5),
    ('news', 'news', 'Últimas notícias', 'Fique por dentro',
        NULL, 'Ver todas', '/noticias', 1, 6),
    ('faq', 'faq', 'Perguntas frequentes', 'Tire suas dúvidas',
        NULL, 'Ver FAQ', '/faq', 1, 7),
    ('cta', 'cta', 'Pronto para começar?', 'Junte-se à comunidade',
        '<p>Junte-se à comunidade e receba novidades em primeira mão.</p>',
        'Entrar na comunidade', '', 1, 8)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
