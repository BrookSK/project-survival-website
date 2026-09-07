<?php

/**
 * Rotas da área administrativa.
 *
 * Todas passam pelo middleware de autenticação. As ações de escrita
 * verificam CSRF no próprio controller.
 *
 * @var \App\Core\Router $router
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

/** @var Router $router */

// Autenticação (acessível sem login)
$router->group(['prefix' => '/admin'], function (Router $router) {
    $router->get('/login', 'Admin\\AuthController@showLogin', [GuestMiddleware::class]);
    $router->post('/login', 'Admin\\AuthController@login', [GuestMiddleware::class]);
    $router->post('/logout', 'Admin\\AuthController@logout');

    // Recuperação de senha
    $router->get('/esqueci-senha', 'Admin\\PasswordController@showRequest', [GuestMiddleware::class]);
    $router->post('/esqueci-senha', 'Admin\\PasswordController@sendReset', [GuestMiddleware::class]);
    $router->get('/redefinir-senha/{token}', 'Admin\\PasswordController@showReset', [GuestMiddleware::class]);
    $router->post('/redefinir-senha', 'Admin\\PasswordController@reset', [GuestMiddleware::class]);
});

// Área protegida
$router->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function (Router $router) {
    $router->get('', 'Admin\\DashboardController@index');
    $router->get('/', 'Admin\\DashboardController@index');

    // Perfil do próprio usuário
    $router->get('/perfil', 'Admin\\ProfileController@edit');
    $router->post('/perfil', 'Admin\\ProfileController@update');
    $router->post('/perfil/senha', 'Admin\\ProfileController@updatePassword');

    // Seções da Home
    $router->get('/home', 'Admin\\HomeSectionController@index');
    $router->get('/home/criar', 'Admin\\HomeSectionController@create');
    $router->post('/home', 'Admin\\HomeSectionController@store');
    $router->post('/home/reordenar', 'Admin\\HomeSectionController@reorder');
    $router->get('/home/{id}/editar', 'Admin\\HomeSectionController@edit');
    $router->post('/home/{id}', 'Admin\\HomeSectionController@update');
    $router->post('/home/{id}/excluir', 'Admin\\HomeSectionController@destroy');

    // Páginas
    $router->get('/paginas', 'Admin\\PageController@index');
    $router->get('/paginas/criar', 'Admin\\PageController@create');
    $router->post('/paginas', 'Admin\\PageController@store');
    $router->get('/paginas/{id}/editar', 'Admin\\PageController@edit');
    $router->post('/paginas/{id}', 'Admin\\PageController@update');
    $router->post('/paginas/{id}/excluir', 'Admin\\PageController@destroy');

    // Notícias
    $router->get('/noticias', 'Admin\\NewsController@index');
    $router->get('/noticias/criar', 'Admin\\NewsController@create');
    $router->post('/noticias', 'Admin\\NewsController@store');
    $router->post('/noticias/bulk', 'Admin\\NewsController@bulk');
    $router->get('/noticias/{id}/editar', 'Admin\\NewsController@edit');
    $router->post('/noticias/{id}', 'Admin\\NewsController@update');
    $router->post('/noticias/{id}/excluir', 'Admin\\NewsController@destroy');

    // Categorias de notícias
    $router->get('/categorias', 'Admin\\CategoryController@index');
    $router->post('/categorias', 'Admin\\CategoryController@store');
    $router->post('/categorias/{id}', 'Admin\\CategoryController@update');
    $router->post('/categorias/{id}/excluir', 'Admin\\CategoryController@destroy');

    // FAQ
    $router->get('/faq', 'Admin\\FaqController@index');
    $router->get('/faq/criar', 'Admin\\FaqController@create');
    $router->post('/faq', 'Admin\\FaqController@store');
    $router->get('/faq/{id}/editar', 'Admin\\FaqController@edit');
    $router->post('/faq/{id}', 'Admin\\FaqController@update');
    $router->post('/faq/{id}/excluir', 'Admin\\FaqController@destroy');

    // Galeria
    $router->get('/galeria', 'Admin\\GalleryController@index');
    $router->get('/galeria/criar', 'Admin\\GalleryController@create');
    $router->post('/galeria', 'Admin\\GalleryController@store');
    $router->get('/galeria/{id}/editar', 'Admin\\GalleryController@edit');
    $router->post('/galeria/{id}', 'Admin\\GalleryController@update');
    $router->post('/galeria/{id}/excluir', 'Admin\\GalleryController@destroy');
    $router->post('/galeria/{id}/itens', 'Admin\\GalleryController@addItem');
    $router->post('/galeria/itens/{id}/excluir', 'Admin\\GalleryController@destroyItem');
    $router->post('/galeria/itens/reordenar', 'Admin\\GalleryController@reorderItems');

    // Vídeos
    $router->get('/videos', 'Admin\\VideoController@index');
    $router->get('/videos/criar', 'Admin\\VideoController@create');
    $router->post('/videos', 'Admin\\VideoController@store');
    $router->post('/videos/reordenar', 'Admin\\VideoController@reorder');
    $router->get('/videos/{id}/editar', 'Admin\\VideoController@edit');
    $router->post('/videos/{id}', 'Admin\\VideoController@update');
    $router->post('/videos/{id}/excluir', 'Admin\\VideoController@destroy');

    // Biblioteca de mídia
    $router->get('/midia', 'Admin\\MediaController@index');
    $router->post('/midia', 'Admin\\MediaController@store');
    $router->post('/midia/{id}', 'Admin\\MediaController@update');
    $router->post('/midia/{id}/excluir', 'Admin\\MediaController@destroy');

    // Banners
    $router->get('/banners', 'Admin\\BannerController@index');
    $router->get('/banners/criar', 'Admin\\BannerController@create');
    $router->post('/banners', 'Admin\\BannerController@store');
    $router->get('/banners/{id}/editar', 'Admin\\BannerController@edit');
    $router->post('/banners/{id}', 'Admin\\BannerController@update');
    $router->post('/banners/{id}/excluir', 'Admin\\BannerController@destroy');

    // Menus
    $router->get('/menus', 'Admin\\MenuController@index');
    $router->post('/menus/itens', 'Admin\\MenuController@storeItem');
    $router->post('/menus/itens/{id}', 'Admin\\MenuController@updateItem');
    $router->post('/menus/itens/{id}/excluir', 'Admin\\MenuController@destroyItem');

    // Mensagens de contato
    $router->get('/mensagens', 'Admin\\MessageController@index');
    $router->get('/mensagens/{id}', 'Admin\\MessageController@show');
    $router->post('/mensagens/{id}/status', 'Admin\\MessageController@updateStatus');
    $router->post('/mensagens/{id}/excluir', 'Admin\\MessageController@destroy');

    // Usuários
    $router->get('/usuarios', 'Admin\\UserController@index');
    $router->get('/usuarios/criar', 'Admin\\UserController@create');
    $router->post('/usuarios', 'Admin\\UserController@store');
    $router->get('/usuarios/{id}/editar', 'Admin\\UserController@edit');
    $router->post('/usuarios/{id}', 'Admin\\UserController@update');
    $router->post('/usuarios/{id}/excluir', 'Admin\\UserController@destroy');

    // Perfis (roles)
    $router->get('/perfis', 'Admin\\RoleController@index');
    $router->get('/perfis/criar', 'Admin\\RoleController@create');
    $router->post('/perfis', 'Admin\\RoleController@store');
    $router->get('/perfis/{id}/editar', 'Admin\\RoleController@edit');
    $router->post('/perfis/{id}', 'Admin\\RoleController@update');
    $router->post('/perfis/{id}/excluir', 'Admin\\RoleController@destroy');

    // Templates de e-mail
    $router->get('/email-templates', 'Admin\\EmailTemplateController@index');
    $router->get('/email-templates/{id}/editar', 'Admin\\EmailTemplateController@edit');
    $router->post('/email-templates/{id}', 'Admin\\EmailTemplateController@update');

    // Notificações administrativas
    $router->get('/notificacoes', 'Admin\\NotificationController@index');
    $router->get('/notificacoes/feed', 'Admin\\NotificationController@feed');
    $router->post('/notificacoes/ler', 'Admin\\NotificationController@markAllRead');

    // Redes sociais
    $router->get('/redes-sociais', 'Admin\\SocialLinkController@index');
    $router->post('/redes-sociais', 'Admin\\SocialLinkController@store');
    $router->post('/redes-sociais/reordenar', 'Admin\\SocialLinkController@reorder');
    $router->post('/redes-sociais/{id}', 'Admin\\SocialLinkController@update');
    $router->post('/redes-sociais/{id}/excluir', 'Admin\\SocialLinkController@destroy');

    // Configurações
    $router->get('/configuracoes', 'Admin\\SettingController@index');
    $router->post('/configuracoes', 'Admin\\SettingController@update');
    $router->post('/configuracoes/email/teste', 'Admin\\SettingController@sendTestEmail');

    // Auditoria
    $router->get('/auditoria', 'Admin\\AuditController@index');

    // Redirecionamentos
    $router->get('/redirects', 'Admin\\RedirectController@index');
    $router->post('/redirects', 'Admin\\RedirectController@store');
    $router->post('/redirects/{id}', 'Admin\\RedirectController@update');
    $router->post('/redirects/{id}/excluir', 'Admin\\RedirectController@destroy');

    // Sistema e diagnóstico
    $router->get('/sistema', 'Admin\\SystemController@index');
    $router->post('/sistema/migrar', 'Admin\\SystemController@migrate');
    $router->post('/sistema/cache/limpar', 'Admin\\SystemController@clearCache');
    $router->get('/diagnostico', 'Admin\\SystemController@diagnostics');
});
