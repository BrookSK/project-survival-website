<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\Redirect;

/**
 * Aplica redirecionamentos administráveis antes das rotas públicas.
 *
 * Se a URI atual corresponder a um redirect ativo, emite o redirect
 * (301/302) e interrompe o fluxo.
 */
class RedirectMiddleware
{
    public function handle(Request $request): bool
    {
        $path = $request->uri();

        try {
            $redirect = (new Redirect())->matchActive($path);
        } catch (\Throwable $e) {
            return true; // tabela ausente antes da migration: não bloqueia
        }

        if (!$redirect) {
            return true;
        }

        $target = $redirect['to_url'];
        // Caminho interno vira URL absoluta; URL externa é usada como está
        if (!preg_match('#^https?://#', $target)) {
            $target = url(ltrim($target, '/'));
        }

        $code = (int) $redirect['status_code'] === 302 ? 302 : 301;
        Response::redirect($target, $code);
        return false;
    }
}
