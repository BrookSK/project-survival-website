-- =====================================================================
-- Seed: 003_settings
-- Descrição: Configurações padrão administráveis pelo painel.
--            Substituem o uso de .env para dados não sensíveis.
-- =====================================================================

INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_secret`, `label`, `description`) VALUES
    -- Gerais
    ('general', 'site_name',        'Nome do Jogo',                    'string',  0, 'Nome do site',        'Nome exibido no site e nos e-mails.'),
    ('general', 'site_tagline',     'A aventura começa aqui',          'string',  0, 'Tagline',             'Frase curta de destaque.'),
    ('general', 'site_description',  'Website oficial do jogo.',       'text',    0, 'Descrição',           'Descrição curta padrão do site.'),
    ('general', 'site_url',          'http://localhost',              'string',  0, 'URL do site',         'URL base pública do site.'),
    ('general', 'contact_email',     'contato@exemplo.com',           'string',  0, 'E-mail de contato',   'E-mail que recebe as mensagens de contato.'),
    ('general', 'maintenance_mode', '0',                              'boolean', 0, 'Modo manutenção',     'Se ativo, exibe página de manutenção no site público.'),

    -- E-mail (SMTP). A senha é marcada como secreta.
    ('email', 'mail_driver',      'smtp',              'string',  0, 'Driver',           'smtp ou mail.'),
    ('email', 'smtp_host',        '',                  'string',  0, 'SMTP Host',        'Servidor SMTP.'),
    ('email', 'smtp_port',        '587',               'integer', 0, 'SMTP Port',        'Porta SMTP (587 TLS, 465 SSL).'),
    ('email', 'smtp_username',    '',                  'string',  0, 'SMTP Username',    'Usuário de autenticação SMTP.'),
    ('email', 'smtp_password',    '',                  'string',  1, 'SMTP Password',    'Senha de autenticação SMTP.'),
    ('email', 'smtp_encryption',  'tls',               'string',  0, 'Criptografia',     'tls, ssl ou none.'),
    ('email', 'mail_from_name',   'Nome do Jogo',      'string',  0, 'Nome remetente',   'Nome exibido como remetente.'),
    ('email', 'mail_from_email',  'noreply@exemplo.com','string', 0, 'E-mail remetente', 'Endereço de envio.'),
    ('email', 'mail_reply_to',    '',                  'string',  0, 'Reply-To',         'Endereço de resposta (opcional).'),

    -- SEO
    ('seo', 'seo_default_title',       'Nome do Jogo | Site Oficial', 'string', 0, 'Título padrão',      'Título usado quando a página não define um.'),
    ('seo', 'seo_default_description', 'Website oficial do jogo.',    'text',   0, 'Descrição padrão',   'Meta description padrão.'),
    ('seo', 'seo_default_keywords',    '',                            'string', 0, 'Palavras-chave',     'Keywords padrão (separadas por vírgula).'),
    ('seo', 'seo_og_image',            '',                            'string', 0, 'Imagem social',      'Imagem Open Graph padrão.'),
    ('seo', 'seo_twitter_handle',      '',                            'string', 0, 'Perfil Twitter/X',   'Ex.: @seujogo.'),

    -- Redes sociais
    ('social', 'social_discord',   '', 'string', 0, 'Discord',   'URL do servidor Discord.'),
    ('social', 'social_twitter',   '', 'string', 0, 'Twitter/X', 'URL do perfil.'),
    ('social', 'social_instagram', '', 'string', 0, 'Instagram', 'URL do perfil.'),
    ('social', 'social_youtube',   '', 'string', 0, 'YouTube',   'URL do canal.'),
    ('social', 'social_steam',     '', 'string', 0, 'Steam',     'URL da página Steam.'),

    -- Sistema
    ('system', 'items_per_page',      '15', 'integer', 0, 'Itens por página', 'Paginação padrão do painel.'),
    ('system', 'allow_registration',  '0',  'boolean', 0, 'Permitir registro','Reservado para uso futuro.')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`);
