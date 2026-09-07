<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Exige usuário autenticado. Redireciona para o login caso contrário.
 */
class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (AuthService::check()) {
            return true;
        }

        Session::flash('error', 'Faça login para acessar o painel.');
        Session::set('__intended', $request->uri());
        Response::redirect('/admin/login');
        return false;
    }
}
