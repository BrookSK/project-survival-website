-- =====================================================================
-- Seed: 006_permissions_v2
-- Descrição: Permissões dos novos módulos do Prompt 2 e concessão automática
--            aos perfis Administrador e Editor.
-- Idempotente.
-- =====================================================================

INSERT INTO `permissions` (`name`, `slug`, `group`) VALUES
    ('Publicar notícias',     'news.publish',    'news'),
    ('Ver mídia',             'media.view',      'media'),
    ('Enviar mídia',          'media.upload',    'media'),
    ('Editar mídia',          'media.edit',      'media'),
    ('Excluir mídia',         'media.delete',    'media'),
    ('Ver seções da home',    'home.view',       'home'),
    ('Editar seções da home', 'home.edit',       'home'),
    ('Ver vídeos',            'videos.view',     'videos'),
    ('Criar vídeos',          'videos.create',   'videos'),
    ('Editar vídeos',         'videos.edit',     'videos'),
    ('Excluir vídeos',        'videos.delete',   'videos'),
    ('Ver redes sociais',     'social.view',     'social'),
    ('Editar redes sociais',  'social.edit',     'social'),
    ('Ver redirecionamentos', 'redirects.view',  'redirects'),
    ('Editar redirecionamentos','redirects.edit','redirects'),
    ('Ver templates de e-mail','email_templates.view','email_templates'),
    ('Editar templates de e-mail','email_templates.edit','email_templates'),
    ('Ver sistema',           'system.view',     'system'),
    ('Gerenciar sistema',     'system.manage',   'system'),
    ('Publicar páginas',      'pages.publish',   'pages')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `group` = VALUES(`group`);

-- Administrador recebe as novas permissões (exceto system.manage por segurança)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN (
    'news.publish','pages.publish',
    'media.view','media.upload','media.edit','media.delete',
    'home.view','home.edit',
    'videos.view','videos.create','videos.edit','videos.delete',
    'social.view','social.edit',
    'redirects.view','redirects.edit',
    'email_templates.view','email_templates.edit',
    'system.view'
  )
ON DUPLICATE KEY UPDATE `role_id` = r.id;

-- Editor recebe permissões de conteúdo e mídia
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'editor'
  AND p.slug IN (
    'news.publish','pages.publish',
    'media.view','media.upload','media.edit',
    'home.view','home.edit',
    'videos.view','videos.create','videos.edit'
  )
ON DUPLICATE KEY UPDATE `role_id` = r.id;
