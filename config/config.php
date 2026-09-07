<?php

/**
 * Configuração principal da aplicação.
 *
 * Contém apenas configurações NÃO sensíveis e valores padrão.
 * Dados sensíveis (chave da app, ambiente real) vêm de config/local.php.
 *
 * Configurações administráveis pelo usuário (nome do site, SMTP, SEO, redes
 * sociais, etc.) NÃO ficam aqui — elas vivem na tabela `settings` e são
 * gerenciadas pelo painel administrativo (Settings Service).
 */

$local = [];
if (is_file(LOCAL_CONFIG_FILE)) {
    $localConfig = require LOCAL_CONFIG_FILE;
    if (is_array($localConfig)) {
        $local = $localConfig;
    }
}

$environment = $local['environment'] ?? ENV_PRODUCTION;
$isProduction = ($environment === ENV_PRODUCTION);

return [
    // Ambiente atual
    'environment' => $environment,
    'debug'       => !$isProduction,

    // Chave da aplicação (assinatura de tokens/cookies). Vazia se ainda não instalado.
    'app_key' => $local['app_key'] ?? '',

    // Fuso horário padrão do PHP
    'timezone' => 'UTC',

    // Configurações de sessão segura
    'session' => [
        'name'            => 'GAMESITE_SESSID',
        'lifetime'        => 7200,          // 2 horas (segundos)
        'path'            => '/',
        'domain'          => '',
        'secure'          => $isProduction, // exige HTTPS em produção
        'httponly'        => true,
        'samesite'        => 'Lax',
        'regenerate_after' => 1800,         // regenera ID a cada 30 min
    ],

    // Segurança / autenticação
    'security' => [
        // Custo do bcrypt para password_hash
        'password_algo'   => PASSWORD_BCRYPT,
        'password_cost'   => 12,

        // Proteção contra brute force no login
        'login_max_attempts'   => 5,        // tentativas antes do bloqueio
        'login_lockout_minutes' => 15,      // duração do bloqueio

        // Token CSRF
        'csrf_token_name' => '_csrf',
        'csrf_field'      => '_token',

        // Expiração de tokens de recuperação de senha (minutos)
        'password_reset_expires' => 60,
    ],

    // Uploads
    'uploads' => [
        'max_size'          => 5 * 1024 * 1024, // 5 MB
        'allowed_mimes'     => [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
            'image/webp' => ['webp'],
            'image/gif'  => ['gif'],
        ],
        'public_dir'  => PUBLIC_UPLOADS_PATH,
        'private_dir' => PRIVATE_UPLOADS_PATH,
    ],

    // Logs
    'logs' => [
        // Em produção nunca exibir erros ao visitante
        'display_errors' => !$isProduction,
        'path'           => LOGS_PATH,
        'level'          => $isProduction ? 'error' : 'debug',
    ],

    // Paginação padrão
    'pagination' => [
        'per_page' => 15,
    ],
];
