-- =====================================================================
-- Seed: 013_privacy_settings
-- Descrição: Configurações de privacidade (dados da empresa, contato do
--            responsável, retenção) e registro dos documentos legais.
--            NÃO contém dados fictícios: campos ficam vazios para o
--            administrador preencher com informações oficiais.
-- Idempotente.
-- =====================================================================

INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_secret`, `label`, `description`) VALUES
    -- Dados da empresa/controlador (preencher com dados oficiais)
    ('privacy', 'company_legal_name', '', 'string', 0, 'Razão social', 'Nome empresarial do controlador dos dados.'),
    ('privacy', 'company_trade_name', '', 'string', 0, 'Nome fantasia', 'Nome fantasia (opcional).'),
    ('privacy', 'company_tax_id',     '', 'string', 0, 'CNPJ', 'Documento da empresa (opcional).'),
    ('privacy', 'company_address',    '', 'text',   0, 'Endereço', 'Endereço do controlador (opcional).'),
    -- Contato de privacidade / encarregado (DPO)
    ('privacy', 'privacy_contact_name',  '', 'string', 0, 'Responsável por privacidade', 'Nome/identificação do responsável (encarregado).'),
    ('privacy', 'privacy_contact_email', '', 'string', 0, 'E-mail de privacidade', 'Canal para exercício de direitos do titular.'),
    ('privacy', 'privacy_contact_channel', '', 'string', 0, 'Canal de atendimento', 'Outro canal de atendimento (opcional).'),
    -- Retenção (documentado; prazos configuráveis, sem inventar prazos legais)
    ('privacy', 'retention_contact_days', '365', 'integer', 0, 'Retenção de mensagens de contato (dias)', 'Tempo de guarda das mensagens do formulário de contato.'),
    ('privacy', 'retention_audit_days',   '730', 'integer', 0, 'Retenção de logs de auditoria (dias)', 'Tempo de guarda dos logs administrativos.'),
    ('privacy', 'retention_export_days',  '7',   'integer', 0, 'Validade da exportação (dias)', 'Prazo para o titular baixar a exportação de dados.'),
    ('privacy', 'privacy_request_sla_days','15',  'integer', 0, 'Prazo interno de resposta (dias)', 'Meta interna para tratar solicitações de titulares.')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`), `type` = VALUES(`type`);

-- Documentos legais versionados. Registra os "tipos"; o conteúdo vive nas
-- versões (criadas/publicadas pelo painel). is_required marca os que exigem
-- aceite do titular.
INSERT INTO `privacy_policies` (`type`, `title`, `slug`, `is_required`) VALUES
    ('privacy',        'Política de Privacidade', 'privacidade',        1),
    ('terms',          'Termos de Uso',           'termos',             1),
    ('purchase_terms', 'Termos de Compra',        'termos-de-compra',   1),
    ('refund',         'Política de Reembolso',   'reembolso',          0),
    ('cookies',        'Política de Cookies',     'cookies',            0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `is_required` = VALUES(`is_required`);
