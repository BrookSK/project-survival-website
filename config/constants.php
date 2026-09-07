<?php

/**
 * Constantes globais da aplicação.
 *
 * Define caminhos absolutos e valores fixos utilizados em todo o sistema.
 * Este arquivo é carregado antes de qualquer outra configuração.
 */

// Diretório raiz do projeto (um nível acima de /config)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Diretórios principais
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('DATABASE_PATH', BASE_PATH . '/database');
define('ROUTES_PATH', BASE_PATH . '/routes');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Subdiretórios de app
define('CONTROLLERS_PATH', APP_PATH . '/Controllers');
define('MODELS_PATH', APP_PATH . '/Models');
define('VIEWS_PATH', APP_PATH . '/Views');
define('CORE_PATH', APP_PATH . '/Core');
define('SERVICES_PATH', APP_PATH . '/Services');
define('HELPERS_PATH', APP_PATH . '/Helpers');
define('MIDDLEWARE_PATH', APP_PATH . '/Middleware');
define('VALIDATORS_PATH', APP_PATH . '/Validators');

// Subdiretórios de storage
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('CACHE_PATH', STORAGE_PATH . '/cache');
define('PRIVATE_UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('BACKUPS_PATH', STORAGE_PATH . '/backups');

// Uploads públicos
define('PUBLIC_UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// Banco de dados
define('SCHEMA_PATH', DATABASE_PATH . '/schema');
define('SEEDS_PATH', DATABASE_PATH . '/seeds');
define('MIGRATIONS_PATH', DATABASE_PATH . '/migrations');

// Arquivos de controle
define('LOCAL_CONFIG_FILE', CONFIG_PATH . '/local.php');
define('INSTALL_LOCK_FILE', CONFIG_PATH . '/installed.lock');

// Versão da aplicação
define('APP_VERSION', '2.2.0');

// Ambientes válidos
define('ENV_DEVELOPMENT', 'development');
define('ENV_PRODUCTION', 'production');
