-- =====================================================================
-- Seed: 007_settings_v2
-- Descrição: Novas configurações do Prompt 2 (geral, SEO, cookies, notícias).
-- Idempotente.
-- =====================================================================

INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_secret`, `label`, `description`) VALUES
    -- Gerais adicionais
    ('general', 'game_name',       'Nome do Jogo',    'string',  0, 'Nome do jogo',        'Nome do jogo (pode diferir do nome do site).'),
    ('general', 'site_logo',       '',                'string',  0, 'Logo',                'Caminho da logo (upload em Mídia).'),
    ('general', 'site_favicon',    '',                'string',  0, 'Favicon',             'Caminho do favicon.'),
    ('general', 'default_share_image', '',            'string',  0, 'Imagem padrão',       'Imagem social padrão.'),
    ('general', 'contact_phone',   '',                'string',  0, 'Telefone',            'Telefone de contato (opcional).'),
    ('general', 'copyright_text',  '',                'string',  0, 'Copyright',           'Texto de copyright do rodapé.'),
    ('general', 'timezone',        'America/Sao_Paulo','string', 0, 'Fuso horário',        'Timezone padrão da aplicação.'),
    ('general', 'locale',          'pt-BR',           'string',  0, 'Idioma',              'Idioma padrão (pt-BR, en, es).'),
    ('general', 'maintenance_message', 'Estamos em manutenção. Voltamos em breve!', 'text', 0, 'Mensagem de manutenção', 'Exibida quando o modo manutenção está ativo.'),
    ('general', 'maintenance_eta', '',                'string',  0, 'Previsão de retorno', 'Texto opcional de previsão de retorno.'),

    -- SEO adicionais
    ('seo', 'seo_google_verification', '',            'string',  0, 'Google Verification', 'Conteúdo da meta google-site-verification.'),
    ('seo', 'seo_indexable',           '1',           'boolean', 0, 'Permitir indexação',  'Se desativado, o site não é indexado por buscadores.'),

    -- Notícias
    ('system', 'featured_news_limit', '3',            'integer', 0, 'Limite de destaques', 'Máximo de notícias em destaque exibidas na home.'),

    -- Cookies / LGPD
    ('cookies', 'cookie_enabled', '0',                'boolean', 0, 'Ativar aviso de cookies', 'Exibe o banner de consentimento de cookies.'),
    ('cookies', 'cookie_text',    'Utilizamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com nossa política.', 'text', 0, 'Texto do aviso', 'Mensagem do banner de cookies.'),
    ('cookies', 'cookie_policy_url', '/politica-de-cookies', 'string', 0, 'Link da política', 'URL da política de cookies.')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`);
