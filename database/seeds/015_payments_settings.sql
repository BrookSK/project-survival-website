-- =====================================================================
-- Seed: 015_payments_settings
-- Descrição: Configurações do grupo 'payments' (Prompt 6): gateway de
--            pagamento (Mercado Pago), ambiente sandbox/produção, chaves e
--            segredos (mascarados), moeda, e a SERVICE ACCOUNT usada para
--            falar com a Game API (server-to-server) na concessão/estorno.
--
-- SEM .env: tudo é configurado pelo painel (Admin -> Configurações ->
-- Pagamentos / Loja). Campos secretos (is_secret=1) NUNCA aparecem em claro
-- na UI e, se enviados vazios, MANTÊM o valor atual.
--
-- NÃO contém credenciais reais: valores ficam vazios para o administrador
-- preencher com dados oficiais. Nada aqui simula pagamento.
-- Idempotente.
-- =====================================================================

INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_secret`, `label`, `description`) VALUES
    -- Gateway / ambiente
    ('payments', 'payments_enabled',      '0',           'boolean', 0, 'Loja/pagamentos habilitados', 'Liga o checkout comercial. Desligado = nenhum pagamento é criado.'),
    ('payments', 'payment_gateway',       'null',        'string',  0, 'Gateway de pagamento', 'Identificador do gateway ativo: "mercadopago" ou "null" (desativado).'),
    ('payments', 'payment_environment',   'sandbox',     'string',  0, 'Ambiente', 'sandbox (testes) ou production (produção). Trocar para produção exige credenciais de produção.'),
    ('payments', 'payment_currency',      'BRL',         'string',  0, 'Moeda', 'Moeda das cobranças (ISO 4217). A Game API é a fonte do preço; esta é a moeda de cobrança.'),
    -- Mercado Pago (credenciais preenchidas pelo admin; access token e webhook secret são secretos)
    ('payments', 'mercadopago_public_key',    '', 'string', 0, 'Mercado Pago — Public Key', 'Chave pública do Mercado Pago (usada no checkout hospedado/tokenização no navegador).'),
    ('payments', 'mercadopago_access_token',  '', 'string', 1, 'Mercado Pago — Access Token', 'Token de acesso server-side do Mercado Pago. Secreto: nunca exibido; deixe vazio para manter.'),
    ('payments', 'mercadopago_webhook_secret','', 'string', 1, 'Mercado Pago — Webhook Secret', 'Segredo para validar a assinatura dos webhooks. Secreto: deixe vazio para manter.'),
    -- Service account para a Game API (concessão/estorno server-to-server)
    ('payments', 'game_api_service_client_id', '', 'string', 0, 'Game API — Service Client ID', 'Identificador do site como serviço junto à Game API (concessão/estorno).'),
    ('payments', 'game_api_service_secret',    '', 'string', 1, 'Game API — Service Secret', 'Segredo da service account. Secreto: nunca exibido; deixe vazio para manter.'),
    -- Política de fulfillment/refund
    ('payments', 'fulfillment_max_attempts', '8',  'integer', 0, 'Fulfillment — máximo de tentativas', 'Número máximo de tentativas de concessão antes de marcar falha permanente.'),
    ('payments', 'refund_default_policy',    'revoke', 'string', 0, 'Política padrão de reembolso', 'Ao reembolsar: "revoke" (revoga entitlement na Game API) ou "keep" (mantém item).')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`), `type` = VALUES(`type`), `is_secret` = VALUES(`is_secret`);
