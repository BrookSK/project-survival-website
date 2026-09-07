-- =====================================================================
-- Seed: 001_permissions
-- Descrição: Permissões base do sistema, agrupadas por módulo.
-- Seeds são idempotentes (INSERT ... ON DUPLICATE KEY UPDATE por slug).
-- =====================================================================

INSERT INTO `permissions` (`name`, `slug`, `group`) VALUES
    ('Ver painel',              'dashboard.view',   'dashboard'),

    ('Ver páginas',             'pages.view',       'pages'),
    ('Criar páginas',           'pages.create',     'pages'),
    ('Editar páginas',          'pages.edit',       'pages'),
    ('Excluir páginas',         'pages.delete',     'pages'),

    ('Ver notícias',            'news.view',        'news'),
    ('Criar notícias',          'news.create',      'news'),
    ('Editar notícias',         'news.edit',        'news'),
    ('Excluir notícias',        'news.delete',      'news'),

    ('Ver categorias',          'categories.view',  'categories'),
    ('Criar categorias',        'categories.create','categories'),
    ('Editar categorias',       'categories.edit',  'categories'),
    ('Excluir categorias',      'categories.delete','categories'),

    ('Ver FAQ',                 'faq.view',         'faq'),
    ('Criar FAQ',               'faq.create',       'faq'),
    ('Editar FAQ',              'faq.edit',         'faq'),
    ('Excluir FAQ',             'faq.delete',       'faq'),

    ('Ver galeria',             'gallery.view',     'gallery'),
    ('Criar galeria',           'gallery.create',   'gallery'),
    ('Editar galeria',          'gallery.edit',     'gallery'),
    ('Excluir galeria',         'gallery.delete',   'gallery'),

    ('Ver banners',             'banners.view',     'banners'),
    ('Criar banners',           'banners.create',   'banners'),
    ('Editar banners',          'banners.edit',     'banners'),
    ('Excluir banners',         'banners.delete',   'banners'),

    ('Ver menus',               'menus.view',       'menus'),
    ('Criar menus',             'menus.create',     'menus'),
    ('Editar menus',            'menus.edit',       'menus'),
    ('Excluir menus',           'menus.delete',     'menus'),

    ('Ver mensagens',           'messages.view',    'messages'),
    ('Editar mensagens',        'messages.edit',    'messages'),
    ('Excluir mensagens',       'messages.delete',  'messages'),

    ('Ver usuários',            'users.view',       'users'),
    ('Criar usuários',          'users.create',     'users'),
    ('Editar usuários',         'users.edit',       'users'),
    ('Excluir usuários',        'users.delete',     'users'),

    ('Ver perfis',              'roles.view',       'roles'),
    ('Criar perfis',            'roles.create',     'roles'),
    ('Editar perfis',           'roles.edit',       'roles'),
    ('Excluir perfis',          'roles.delete',     'roles'),

    ('Ver configurações',       'settings.view',    'settings'),
    ('Editar configurações',    'settings.edit',    'settings'),

    ('Ver auditoria',           'audit.view',       'audit')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `group` = VALUES(`group`);
