-- =====================================================================
-- Seed: 012_permissions_privacy
-- Descrição: Permissões da camada de privacidade/LGPD (Prompt 5) e concessão
--            ao perfil Administrador. O Editor NÃO recebe estas permissões
--            (segregação: conteúdo != dados pessoais/titulares).
-- Idempotente.
-- =====================================================================

INSERT INTO `permissions` (`name`, `slug`, `group`) VALUES
    ('Ver privacidade',              'privacy.view',     'privacy'),
    ('Gerenciar documentos legais',  'privacy.manage',   'privacy'),
    ('Tratar solicitações de titular','privacy.requests','privacy'),
    ('Exportar dados de titular',    'privacy.export',   'privacy'),
    ('Executar exclusão de dados',   'privacy.delete',   'privacy'),
    ('Ver auditoria de privacidade', 'privacy.audit',    'privacy'),
    ('Configurar privacidade',       'privacy.settings', 'privacy')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `group` = VALUES(`group`);

-- Administrador recebe visão e gestão, mas exclusão/exportação exigem concessão
-- explícita (operações de maior risco). Ajuste conforme sua política interna.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN (
    'privacy.view',
    'privacy.manage',
    'privacy.requests',
    'privacy.audit',
    'privacy.settings'
  )
ON DUPLICATE KEY UPDATE `role_id` = r.id;
