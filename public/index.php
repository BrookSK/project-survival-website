<?php

/**
 * Front Controller.
 *
 * Único ponto de entrada da aplicação. Inicializa constantes, autoloader,
 * bootstrap e despacha a requisição pelo roteador.
 */

declare(strict_types=1);

// Constantes e caminhos
require dirname(__DIR__) . '/config/constants.php';

// Autoloader PSR-4
require CORE_PATH . '/Autoloader.php';

$autoloader = new App\Core\Autoloader();
$autoloader->addNamespace('App', APP_PATH);
$autoloader->register();

// Inicializa e executa a aplicação
$app = new App\Core\App();
$app->boot();
$app->run();
