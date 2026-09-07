<?php

/**
 * Bootstrap dos testes.
 *
 * Carrega constantes e o autoloader do projeto sem tocar no banco de dados,
 * de modo que os testes de unidade rodem em qualquer ambiente PHP (inclusive
 * sem as extensões pdo_mysql/gd). Testes que dependam de DB devem ser marcados
 * como "skip" quando a conexão não estiver disponível.
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once APP_PATH . '/Core/Autoloader.php';

$autoloader = new \App\Core\Autoloader();
$autoloader->addNamespace('App', APP_PATH);
$autoloader->register();

// Config mínima para os serviços que a consultam.
\App\Core\Config::load();

// Helpers globais (str_slug, e, etc.).
require_once HELPERS_PATH . '/helpers.php';

require_once __DIR__ . '/TestCase.php';
