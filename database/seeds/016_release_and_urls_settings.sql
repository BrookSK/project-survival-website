-- =====================================================================
-- Seed: 016_release_and_urls_settings
-- Descrição: Configurações de distribuição/download (canal + cache de
--            release), metadados da integração (versão da API, manutenção) e
--            as URLs públicas do site/jogo (combinadas com a equipe do jogo).
--            Sem .env: tudo administrável pelo painel. NADA hardcoded no código.
-- Idempotente (ON DUPLICATE KEY UPDATE mantém o valor atual, atualiza rótulos).
-- =====================================================================

INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_secret`, `label`, `description`) VALUES
    -- Metadados da integração (grupo game_api existente)
    ('game_api', 'game_api_version',     'v1', 'string',  0, 'Versão da API', 'Rótulo da versão consumida (a autoridade real é o header X-API-Version).'),
    ('game_api', 'game_api_maintenance', '0',  'boolean', 0, 'Modo de manutenção', 'Desliga a experiência dependente da API sem apagar a configuração.'),

    -- Modo de fulfillment (grupo payments existente). Padrão OFICIAL: a Game API
    -- concede via webhook do provedor de pagamento; o site NÃO concede itens.
    ('payments', 'fulfillment_mode', 'game_webhook', 'string', 0, 'Modo de concessão', 'game_webhook (oficial: a Game API concede via webhook do provedor) ou commerce_endpoint (endpoint dedicado, só com contrato oficial).'),

    -- Releases / Download (consumo do contrato público da Game API)
    ('releases', 'release_channel',   'stable', 'string',  0, 'Canal de release', 'Canal público exibido no site: stable, beta ou dev (padrão stable).'),
    ('releases', 'release_cache_ttl', '900',    'integer', 0, 'TTL do cache de release (s)', 'Tempo de cache das informações de release/download (0 = sem expirar).'),
    ('releases', 'download_allowed_hosts', '', 'string', 0, 'Hosts permitidos p/ download', 'Allowlist opcional (CSV) de hosts do instalador/CDN, para o redirect da rota permanente. Vazio = confia nos hosts da API/release.'),

    -- URLs públicas (combine os domínios com a equipe do jogo).
    -- Vazias por padrão: o site usa fallback interno quando não preenchidas.
    ('website_urls', 'site_website_url',  '', 'string', 0, 'Website URL', 'URL pública do site (ex.: https://projectsurvival.example).'),
    ('website_urls', 'site_download_url', '', 'string', 0, 'Download URL', 'Página pública de download (padrão: /download).'),
    ('website_urls', 'site_store_url',    '', 'string', 0, 'Store URL', 'Página da loja (padrão: /loja).'),
    ('website_urls', 'site_account_url',  '', 'string', 0, 'Account URL', 'Área da conta (padrão: /conta).'),
    ('website_urls', 'site_support_url',  '', 'string', 0, 'Support URL', 'Canal de suporte (padrão: /contato).'),
    ('website_urls', 'site_privacy_url',  '', 'string', 0, 'Privacy URL', 'Política de Privacidade (padrão: /privacidade).'),
    ('website_urls', 'site_terms_url',    '', 'string', 0, 'Terms URL', 'Termos de Uso (padrão: /termos).')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`), `type` = VALUES(`type`);
