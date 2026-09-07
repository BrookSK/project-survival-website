<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\GameApi\PlayerSession;

/**
 * Exige um jogador autenticado (sessão da API do jogo) para acessar a área
 * "Minha conta". Sem sessão, redireciona para /login guardando o destino.
 *
 * É distinto do AuthMiddleware (que protege o painel administrativo do site).
 */
class PlayerAuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (PlayerSession::check()) {
            return true;
        }

        Session::flash('error', 'Entre na sua conta para continuar.');
        Session::set('__player_intended', $request->uri());
        Response::redirect('/login');
        return false;
    }
}
