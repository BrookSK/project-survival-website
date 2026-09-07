-- =====================================================================
-- Seed: 011_game_api_settings
-- Descrição: Configurações da integração com a API oficial do Project Survival
--            (Prompt 3). Grupos 'game_api' (conexão) e 'game_api_cache' (cache).
--            Sem .env: toda a configuração é administrável pelo painel.
-- Idempotente (ON DUPLICATE KEY UPDATE mantém o valor atual, atualiza rótulos).
-- =====================================================================

INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_secret`, `label`, `description`) VALUES
    -- Conexão com a API do jogo
    ('game_api', 'game_api_enabled',   '0', 'boolean', 0, 'Ativar integração', 'Habilita a comunicação do site com a API do jogo.'),
    ('game_api', 'game_api_base_url',  'http://localhost:4000/api/v1', 'string', 0, 'URL base da API', 'Ex.: https://api.seudominio.com/api/v1 (inclua /api/v1).'),
    ('game_api', 'game_api_client_id', '', 'string', 0, 'Client ID', 'Identificador público do cliente (não é segredo).'),
    ('game_api', 'game_api_timeout',   '8', 'integer', 0, 'Timeout (segundos)', 'Tempo máximo de espera por resposta da API (1 a 30).'),

    -- Cache das leituras públicas da API
    ('game_api_cache', 'game_api_cache_enabled',        '1',   'boolean', 0, 'Ativar cache', 'Cacheia respostas públicas da API para tolerar indisponibilidade.'),
    ('game_api_cache', 'game_api_cache_ttl_news',       '300', 'integer', 0, 'TTL notícias (s)', 'Tempo de cache das notícias do jogo.'),
    ('game_api_cache', 'game_api_cache_ttl_events',     '300', 'integer', 0, 'TTL eventos (s)', 'Tempo de cache dos eventos ativos.'),
    ('game_api_cache', 'game_api_cache_ttl_store',      '300', 'integer', 0, 'TTL loja/produtos (s)', 'Tempo de cache do catálogo de produtos.'),
    ('game_api_cache', 'game_api_cache_ttl_categories', '600', 'integer', 0, 'TTL categorias (s)', 'Tempo de cache das categorias da loja.'),
    ('game_api_cache', 'game_api_cache_ttl_config',     '600', 'integer', 0, 'TTL config do jogo (s)', 'Tempo de cache de config/feature flags/versão.')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`), `type` = VALUES(`type`);
