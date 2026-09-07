<?php

/**
 * Configuração de conexão com o banco de dados.
 *
 * As credenciais NÃO ficam aqui. Elas são lidas de config/local.php,
 * que é gerado na instalação e não é versionado.
 *
 * Este arquivo apenas normaliza e fornece valores padrão seguros.
 */

$local = [];
if (is_file(LOCAL_CONFIG_FILE)) {
    $localConfig = require LOCAL_CONFIG_FILE;
    if (is_array($localConfig) && isset($localConfig['database']) && is_array($localConfig['database'])) {
        $local = $localConfig['database'];
    }
}

return [
    'driver'    => $local['driver']    ?? 'mysql',
    'host'      => $local['host']      ?? '127.0.0.1',
    'port'      => (int) ($local['port'] ?? 3306),
    'name'      => $local['name']      ?? '',
    'user'      => $local['user']      ?? '',
    'password'  => $local['password']  ?? '',
    'charset'   => $local['charset']   ?? 'utf8mb4',
    'collation' => $local['collation'] ?? 'utf8mb4_unicode_ci',

    // Opções padrão de PDO (segurança e consistência)
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_PERSISTENT         => false,
    ],
];
