<?php

/**
 * Rotas do site público.
 *
 * @var \App\Core\Router $router
 */

use App\Core\Router;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\RedirectMiddleware;

/** @var Router $router */

// SEO técnico (sempre acessível, mesmo em manutenção)
$router->get('/sitemap.xml', 'Site\\SeoController@sitemap');
$router->get('/robots.txt', 'Site\\SeoController@robots');

// Rotas públicas do site (redirects administráveis + modo de manutenção)
$router->group(['middleware' => [RedirectMiddleware::class, MaintenanceMiddleware::class]], function (Router $router) {
    // Home
    $router->get('/', 'Site\\HomeController@index');

    // Páginas institucionais / de conteúdo
    $router->get('/sobre', 'Site\\PageController@show');
    $router->get('/gameplay', 'Site\\PageController@show');

    // Notícias
    $router->get('/noticias', 'Site\\NewsController@index');
    $router->get('/noticias/{slug}', 'Site\\NewsController@show');

    // Galeria
    $router->get('/galeria', 'Site\\GalleryController@index');
    $router->get('/galeria/{slug}', 'Site\\GalleryController@album');

    // Vídeos
    $router->get('/videos', 'Site\\VideoController@index');

    // FAQ
    $router->get('/faq', 'Site\\FaqController@index');

    // Contato
    $router->get('/contato', 'Site\\ContactController@index');
    $router->post('/contato', 'Site\\ContactController@store');

    // Páginas dinâmicas por slug (deve ficar por último para não capturar rotas fixas)
    $router->get('/p/{slug}', 'Site\\PageController@dynamic');
});
