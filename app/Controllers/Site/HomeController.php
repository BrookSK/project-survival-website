<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\GalleryAlbum;
use App\Models\HomeSection;
use App\Models\News;
use App\Models\Video;
use App\Services\SeoService;

/**
 * Página inicial do site público — montada de forma modular a partir das
 * seções configuráveis (home_sections) e das fontes de dados de cada tipo.
 */
class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $banners = (new Banner())->activeByPosition('home_hero');
        $sections = (new HomeSection())->activeOrdered();

        // Dados sob demanda, apenas para os tipos de seção presentes
        $types = array_column($sections, 'type');
        $data = [];

        if (in_array('news', $types, true)) {
            $limit = max(1, (int) setting('featured_news_limit', 3));
            $newsModel = new News();
            $featured = $newsModel->featured($limit);
            $data['news'] = $featured ?: $newsModel->latest($limit);
        }
        if (in_array('faq', $types, true)) {
            $data['faqs'] = (new Faq())->activeGrouped();
        }
        if (in_array('screenshots', $types, true)) {
            $data['albums'] = (new GalleryAlbum())->activeWithCover();
        }
        if (in_array('trailer', $types, true)) {
            $data['trailer'] = (new Video())->featured();
        }

        // JSON-LD Organization + WebSite
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => setting('game_name', setting('site_name', 'Jogo')),
            'url'      => url(),
        ];
        if ($logo = setting('site_logo')) {
            $jsonLd['logo'] = preg_match('#^https?://#', $logo) ? $logo : uploaded($logo);
        }

        $this->viewSite('site.home', [
            'seo'        => SeoService::build(['type' => 'website']),
            'jsonLd'     => $jsonLd,
            'hero'       => $banners[0] ?? null,
            'banners'    => $banners,
            'sections'   => $sections,
            'sectionData'=> $data,
        ]);
    }
}
