<?php

/**
 * Rotas do instalador.
 *
 * Carregadas apenas quando o sistema NÃO está instalado (ver App::run()).
 * Qualquer URL não relacionada à instalação é redirecionada para /install.
 *
 * @var \App\Core\Router $router
 */

use App\Core\Router;

/** @var Router $router */

$router->get('/install', 'InstallController@index');
$router->post('/install', 'InstallController@process');

// Raiz e rotas de nível único redirecionam ao instalador enquanto não instalado.
$redirectToInstall = function ($request) {
    \App\Core\Response::redirect('/install');
};
$router->get('/', $redirectToInstall);
$router->get('/{any:*}', $redirectToInstall);
