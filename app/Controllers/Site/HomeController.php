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
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameContentService;
use App\Services\GameApi\GameStoreService;
use App\Services\SeoService;

/**
 * Página inicial do site público — montada de forma modular a partir das
 * seções configuráveis (home_sections) e das fontes de dados de cada tipo.
 *
 * Quando a integração com a API do jogo está ativa, a Home é enriquecida com
 * notícias, eventos e produtos em destaque vindos da API (com cache/fallback).
 * Se a API estiver fora do ar, essas seções simplesmente não aparecem — o
 * restante da Home (conteúdo local/CMS) continua funcionando.
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

        // Enriquecimento com a API do jogo (aditivo, tolerante a falhas).
        $gameApiOn = GameApiConfig::isEnabled();
        $gameNews = [];
        $gameEvents = [];
        $featuredProducts = [];

        if ($gameApiOn) {
            $content = new GameContentService();
            try {
                $gameNews = $content->news(3);
            } catch (GameApiException $e) {
                $gameNews = [];
            }
            try {
                $gameEvents = $content->events();
            } catch (GameApiException $e) {
                $gameEvents = [];
            }
            try {
                $featuredProducts = (new GameStoreService())->featured(4);
            } catch (GameApiException $e) {
                $featuredProducts = [];
            }
        }

        $this->viewSite('site.home', [
            'seo'             => SeoService::build(['type' => 'website']),
            'jsonLd'          => $jsonLd,
            'hero'            => $banners[0] ?? null,
            'banners'         => $banners,
            'sections'        => $sections,
            'sectionData'     => $data,
            'gameApiOn'       => $gameApiOn,
            'gameNews'        => $gameNews,
            'gameEvents'      => $gameEvents,
            'featuredProducts'=> $featuredProducts,
        ]);
    }
}
