-- =====================================================================
-- Seed: 014_permissions_store
-- Descrição: Permissões da camada comercial/loja (Prompt 6) e concessão ao
--            perfil Administrador. Operações de MAIOR RISCO (refunds) NÃO são
--            concedidas automaticamente — exigem atribuição explícita.
--            O Editor NÃO recebe estas permissões (conteúdo != financeiro).
-- Idempotente.
-- =====================================================================

INSERT INTO `permissions` (`name`, `slug`, `group`) VALUES
    ('Ver loja/comercial',            'store.view',           'store'),
    ('Gerenciar pedidos',             'store.orders',         'store'),
    ('Ver pagamentos/transações',     'store.payments',       'store'),
    ('Executar reembolsos',           'store.refunds',        'store'),
    ('Gerenciar fulfillment',         'store.fulfillment',    'store'),
    ('Gerenciar produtos (catálogo)', 'store.products',       'store'),
    ('Gerenciar cupons',              'store.coupons',        'store'),
    ('Ver reconciliação',             'store.reconciliation', 'store'),
    ('Ver relatórios comerciais',     'store.reports',        'store'),
    ('Configurar loja/pagamentos',    'store.settings',       'store'),
    ('Ver webhooks',                  'store.webhooks',       'store')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `group` = VALUES(`group`);

-- Administrador recebe visão e gestão operacional. `store.refunds` (estorno,
-- que pode disparar revogação de entitlement na Game API) NÃO é concedido
-- automaticamente — atribua manualmente a quem for responsável financeiro.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN (
    'store.view',
    'store.orders',
    'store.payments',
    'store.fulfillment',
    'store.products',
    'store.coupons',
    'store.reconciliation',
    'store.reports',
    'store.settings',
    'store.webhooks'
  )
ON DUPLICATE KEY UPDATE `role_id` = r.id;
