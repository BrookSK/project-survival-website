<?php

/**
 * Rotas do site público.
 *
 * @var \App\Core\Router $router
 */

use App\Core\Router;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\PlayerAuthMiddleware;
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

    // ---- Integração com a API do jogo (público) ----
    // Autenticação do jogador (contas pertencem à API do jogo).
    $router->get('/login', 'Site\\AuthController@loginForm');
    $router->post('/login', 'Site\\AuthController@login');
    $router->get('/criar-conta', 'Site\\AuthController@registerForm');
    $router->post('/criar-conta', 'Site\\AuthController@register');
    $router->post('/logout', 'Site\\AuthController@logout');

    // Loja (catálogo público da API; compra apenas inicia pedido pendente).
    $router->get('/loja', 'Site\\StoreController@index');
    $router->post('/loja/comprar', 'Site\\StoreController@purchase');
    $router->get('/loja/produto/{id}', 'Site\\StoreController@show');

    // Eventos ativos do jogo.
    $router->get('/eventos', 'Site\\EventsController@index');

    // Área do jogador (exige sessão do jogador — API do jogo).
    $router->group(['middleware' => [PlayerAuthMiddleware::class]], function (Router $router) {
        $router->get('/conta', 'Site\\AccountController@overview');
        $router->get('/conta/inventario', 'Site\\AccountController@inventory');
        $router->get('/conta/resgatar', 'Site\\AccountController@redeemForm');
        $router->post('/conta/resgatar', 'Site\\AccountController@redeem');
        $router->get('/conta/seguranca', 'Site\\AccountController@securityForm');
        $router->post('/conta/seguranca', 'Site\\AccountController@changePassword');
    });

    // Páginas dinâmicas por slug (deve ficar por último para não capturar rotas fixas)
    $router->get('/p/{slug}', 'Site\\PageController@dynamic');
});
