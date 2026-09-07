-- =====================================================================
-- Seed: 009_email_templates
-- Descrição: Templates padrão de e-mail com placeholders.
-- Idempotente por `key`.
-- =====================================================================

INSERT INTO `email_templates` (`key`, `name`, `subject`, `body`, `placeholders`, `is_active`) VALUES
    ('contact_received', 'Contato recebido', '[Contato] {{subject}}',
        '<h2>Nova mensagem de contato</h2><p><strong>Nome:</strong> {{name}}</p><p><strong>E-mail:</strong> {{email}}</p><p><strong>Assunto:</strong> {{subject}}</p><p><strong>Mensagem:</strong></p><p>{{message}}</p>',
        '{{name}}, {{email}}, {{subject}}, {{message}}, {{site_name}}', 1),
    ('password_reset', 'Recuperação de senha', 'Redefinição de senha - {{site_name}}',
        '<h2>Redefinição de senha</h2><p>Olá,</p><p>Você solicitou a redefinição de senha em <strong>{{site_name}}</strong>.</p><p>Clique no link abaixo para criar uma nova senha (válido por {{expires}} minutos):</p><p><a href="{{link}}">{{link}}</a></p><p>Se você não fez esta solicitação, ignore este e-mail.</p>',
        '{{site_name}}, {{link}}, {{expires}}', 1),
    ('smtp_test', 'Teste de SMTP', 'Teste de e-mail - {{site_name}}',
        '<p>Este é um e-mail de teste enviado por <strong>{{site_name}}</strong>.</p><p>Se você recebeu esta mensagem, sua configuração SMTP está funcionando corretamente.</p>',
        '{{site_name}}', 1),
    ('admin_notification', 'Notificação administrativa', '{{subject}} - {{site_name}}',
        '<h2>{{title}}</h2><p>{{message}}</p>',
        '{{title}}, {{subject}}, {{message}}, {{site_name}}', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
