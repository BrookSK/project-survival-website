<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameContentService;
use App\Services\GameApi\GameReleaseService;
use App\Services\GameApi\GameStatusService;
use App\Services\SeoService;

/**
 * Página pública de atualizações (/updates).
 *
 * Consome a fonte OFICIAL: a versão/changelog vêm da release atual
 * (GameReleaseService) e as novidades das notícias do jogo (GameContentService).
 * O site NÃO implementa o updater nem publica versões — apenas exibe.
 * Tolerante a falha (nunca quebra): sem API, mostra estado amigável.
 */
class UpdatesController extends Controller
{
    private GameReleaseService $releases;
    private GameContentService $content;

    public function __construct(?GameReleaseService $releases = null, ?GameContentService $content = null)
    {
        $this->releases = $releases ?? new GameReleaseService();
        $this->content = $content ?? new GameContentService();
    }

    public function index(Request $request): void
    {
        $release = null;
        $news = [];

        if (GameApiConfig::isEnabled() && !GameApiConfig::maintenance()) {
            $release = $this->releases->latest();
            $news = $this->content->news(10);
        }

        $seo = SeoService::build([
            'title'       => 'Atualizações',
            'description' => 'Versão atual, novidades e correções do Project Survival.',
            'canonical'   => url('updates'),
            'type'        => 'website',
        ]);

        $this->viewSite('site.updates.index', [
            'title'   => 'Atualizações',
            'seo'     => $seo,
            'release' => $release,
            'news'    => $news,
            'status'  => (new GameStatusService())->status(),
        ]);
    }
}
