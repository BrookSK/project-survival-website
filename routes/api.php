<?php

/**
 * Rotas de API (AJAX/fetch).
 *
 * Endpoints leves, públicos e somente-leitura, consumidos pelo front-end via
 * fetch e preparados para integrações futuras. Respostas sempre em JSON.
 *
 * Convenções:
 *  - Prefixo /api.
 *  - Sempre Response::json (define Content-Type e status).
 *  - Nunca expõem dados sensíveis nem exigem sessão (conteúdo já público).
 *
 * @var \App\Core\Router $router
 */

use App\Core\Response;
use App\Core\Router;

/** @var Router $router */

$router->group(['prefix' => '/api'], function (Router $router) {

    // Health check — status da aplicação e versão.
    $router->get('/health', function ($request) {
        Response::json([
            'status'  => 'ok',
            'version' => APP_VERSION,
            'time'    => date('c'),
        ]);
    });

    // Notícias publicadas mais recentes (para widgets/integrações).
    $router->get('/news', function ($request) {
        $limit = (int) $request->query('limit', 6);
        $limit = max(1, min($limit, 20));

        try {
            $news = (new \App\Models\News())->latest($limit);
        } catch (\Throwable $e) {
            $news = [];
        }

        $items = array_map(static function (array $n): array {
            return [
                'title'        => $n['title'] ?? '',
                'slug'         => $n['slug'] ?? '',
                'url'          => url('noticias/' . ($n['slug'] ?? '')),
                'excerpt'      => excerpt((string) ($n['excerpt'] ?? $n['content'] ?? ''), 160),
                'image'        => !empty($n['featured_image']) ? uploaded($n['featured_image']) : null,
                'published_at' => $n['published_at'] ?? null,
            ];
        }, $news);

        Response::json(['data' => $items, 'count' => count($items)]);
    });
});
