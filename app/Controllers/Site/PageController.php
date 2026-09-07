<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Page;
use App\Services\SeoService;

/**
 * Exibe páginas de conteúdo publicadas.
 *
 * - show(): rotas fixas (/sobre, /gameplay) — deriva o slug da própria URI.
 * - dynamic(): rota /p/{slug} para páginas adicionais criadas no painel.
 */
class PageController extends Controller
{
    public function show(Request $request): void
    {
        // Slug derivado da URI (ex.: "/sobre" -> "sobre")
        $slug = trim($request->uri(), '/');
        $this->render($slug);
    }

    public function dynamic(Request $request, array $params): void
    {
        $this->render((string) ($params['slug'] ?? ''));
    }

    private function render(string $slug): void
    {
        $pageModel = new Page();
        $page = $pageModel->findPublishedBySlug($slug);

        if (!$page) {
            $this->abort(404);
        }

        $meta = $pageModel->meta((int) $page['id']) ?? [];

        $this->viewSite('site.page', [
            'seo' => SeoService::build([
                'title'       => $meta['seo_title'] ?: $page['title'],
                'description' => $meta['seo_description'] ?? '',
                'keywords'    => $meta['seo_keywords'] ?? '',
                'image'       => $meta['og_image'] ?? '',
                'robots'      => $meta['robots'] ?? 'index,follow',
                'type'        => 'article',
            ]),
            'page' => $page,
        ]);
    }
}
