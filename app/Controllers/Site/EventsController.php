<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameContentService;

/**
 * Eventos ativos do jogo (GET /events, cacheado com fallback stale).
 *
 * Não inventa eventos expirados: exibe apenas o que a API retorna. Sem eventos,
 * mostra uma mensagem curta.
 */
class EventsController extends Controller
{
    public function index(Request $request): void
    {
        if (!GameApiConfig::isEnabled()) {
            $this->abort(404);
        }

        $events = [];
        $offline = false;
        try {
            $events = (new GameContentService())->events();
        } catch (GameApiException $e) {
            $offline = true;
        }

        $this->viewSite('site.events.index', [
            'title'   => 'Eventos',
            'events'  => $events,
            'offline' => $offline,
        ]);
    }
}
