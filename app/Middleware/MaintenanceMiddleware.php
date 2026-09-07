<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\SettingsService;

/**
 * Modo de manutenção do site público.
 *
 * Quando ativado (settings.maintenance_mode), exibe uma página de manutenção
 * para visitantes. Usuários autenticados no painel continuam navegando
 * normalmente (para conseguirem validar o site).
 */
class MaintenanceMiddleware
{
    public function handle(Request $request): bool
    {
        $enabled = (bool) SettingsService::get('maintenance_mode', false);

        if (!$enabled || AuthService::check()) {
            return true;
        }

        Response::status(503);
        header('Retry-After: 3600');
        echo View::render('site.maintenance', [], null);
        return false;
    }
}
