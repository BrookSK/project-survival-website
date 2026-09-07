-- =====================================================================
-- Seed: 005_menus
-- Descrição: Menus padrão de header e footer com itens iniciais.
-- =====================================================================

INSERT INTO `menus` (`name`, `location`) VALUES
    ('Menu Principal', 'header'),
    ('Menu Rodapé',    'footer')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Itens do header
INSERT INTO `menu_items` (`menu_id`, `label`, `url`, `sort_order`)
SELECT m.id, t.label, t.url, t.sort_order
FROM `menus` m
JOIN (
    SELECT 'Início'    AS label, '/'          AS url, 1 AS sort_order
    UNION ALL SELECT 'Sobre',     '/sobre',     2
    UNION ALL SELECT 'Gameplay',  '/gameplay',  3
    UNION ALL SELECT 'Notícias',  '/noticias',  4
    UNION ALL SELECT 'Galeria',   '/galeria',   5
    UNION ALL SELECT 'FAQ',       '/faq',       6
    UNION ALL SELECT 'Contato',   '/contato',   7
) t
WHERE m.location = 'header'
ON DUPLICATE KEY UPDATE `label` = `menu_items`.`label`;

-- Itens do footer
INSERT INTO `menu_items` (`menu_id`, `label`, `url`, `sort_order`)
SELECT m.id, t.label, t.url, t.sort_order
FROM `menus` m
JOIN (
    SELECT 'Política de Privacidade' AS label, '/politica-de-privacidade' AS url, 1 AS sort_order
    UNION ALL SELECT 'Termos de Uso', '/termos-de-uso', 2
    UNION ALL SELECT 'Contato',       '/contato',       3
) t
WHERE m.location = 'footer'
ON DUPLICATE KEY UPDATE `label` = `menu_items`.`label`;
