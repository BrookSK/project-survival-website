<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\News;
use App\Models\NewsCategory;
use App\Services\SeoService;

/**
 * Listagem e leitura de notícias no site público.
 */
class NewsController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 9;
        $category = trim((string) $request->query('categoria', ''));

        $newsModel = new News();
        $result = $newsModel->published($page, $perPage, $category ?: null);

        $this->viewSite('site.news.index', [
            'seo'        => SeoService::build(['title' => 'Notícias']),
            'items'      => $result['items'],
            'total'      => $result['total'],
            'page'       => $page,
            'perPage'    => $perPage,
            'category'   => $category,
            'categories' => (new NewsCategory())->withPublished(),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $newsModel = new News();
        $news = $newsModel->findPublishedBySlug($slug);

        if (!$news) {
            $this->abort(404);
        }

        $newsModel->incrementViews((int) $news['id']);

        $image = $news['og_image'] ?: $news['featured_image'];
        $jsonLd = [
            '@context'      => 'https://schema.org',
            '@type'         => 'NewsArticle',
            'headline'      => $news['title'],
            'datePublished' => $news['published_at'],
            'dateModified'  => $news['updated_at'] ?? $news['published_at'],
            'description'   => $news['excerpt'] ?: '',
            'mainEntityOfPage' => url('noticias/' . $news['slug']),
        ];
        if ($image) {
            $jsonLd['image'] = preg_match('#^https?://#', $image) ? $image : uploaded($image);
        }

        $this->viewSite('site.news.show', [
            'seo' => SeoService::build([
                'title'       => $news['seo_title'] ?: $news['title'],
                'description' => $news['seo_description'] ?: $news['excerpt'],
                'image'       => $news['og_image'] ?: $news['featured_image'],
                'type'        => 'article',
            ]),
            'jsonLd'     => $jsonLd,
            'news'       => $news,
            'categories' => $newsModel->categories((int) $news['id']),
            'related'    => $newsModel->latest(3),
        ]);
    }
}
