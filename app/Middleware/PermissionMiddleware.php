<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Services\AuthService;

/**
 * Verifica uma permissão específica. Como o roteador instancia middlewares
 * sem argumentos, esta classe é configurável por subclasses ou usada via
 * verificação direta no controller (Controller::authorize).
 *
 * Preferimos a checagem no controller (Controller::authorize) por ser mais
 * granular por ação; este middleware fica disponível para bloqueios amplos.
 */
class PermissionMiddleware
{
    protected string $permission = '';

    public function handle(Request $request): bool
    {
        if ($this->permission === '' || AuthService::can($this->permission)) {
            return true;
        }

        (new Router())->abort(403, $request);
        return false;
    }
}
