-- =====================================================================
-- Seed: 002_roles
-- Descrição: Perfis base. O Super Administrador recebe TODAS as permissões
--            (o SettingsService/AuthService trata super-admin como acesso total).
-- =====================================================================

INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
    ('Super Administrador', 'super-admin', 'Acesso total e irrestrito ao sistema.', 1),
    ('Administrador',       'admin',       'Acesso administrativo amplo.', 1),
    ('Editor',              'editor',      'Gerencia conteúdo do site.', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- Administrador: todas as permissões, exceto gerenciar perfis/usuários avançados
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'admin'
  AND p.slug NOT IN ('roles.create','roles.edit','roles.delete','users.delete')
ON DUPLICATE KEY UPDATE `role_id` = r.id;

-- Editor: apenas conteúdo
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'editor'
  AND p.`group` IN ('dashboard','pages','news','categories','faq','gallery','banners','menus','messages')
  AND p.slug NOT LIKE '%.delete'
ON DUPLICATE KEY UPDATE `role_id` = r.id;
