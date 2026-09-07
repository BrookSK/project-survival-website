<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Permite apenas visitantes (não autenticados).
 * Redireciona usuários logados para o dashboard.
 */
class GuestMiddleware
{
    public function handle(Request $request): bool
    {
        if (AuthService::check()) {
            Response::redirect('/admin');
            return false;
        }
        return true;
    }
}
