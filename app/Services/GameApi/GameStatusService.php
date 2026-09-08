<?php

namespace App\Services\GameApi;

use App\Services\CacheService;

/**
 * Status público do serviço do jogo (para exibir "online / manutenção" e a
 * versão atual no site), com CACHE curto para não bater no /health a cada
 * carregamento de página.
 *
 * Nunca lança e nunca bloqueia a página: em falha, devolve offline.
 */
class GameStatusService
{
    private const CACHE_KEY = 'gameapi:status:public';
    private const TTL = 60; // 1 min: status "quase em tempo real" sem flood.

    private GameHealthService $health;

    public function __construct(?GameHealthService $health = null)
    {
        $this->health = $health ?? new GameHealthService();
    }

    /**
     * Estado consolidado para a UI.
     *
     * @return array{
     *   enabled:bool, maintenance:bool, online:bool,
     *   state:string, label:string, api_version:?string
     * }
     *   state ∈ disabled|maintenance|online|offline
     */
    public function status(): array
    {
        $enabled = GameApiConfig::isEnabled();
        $maintenance = GameApiConfig::maintenance();

        $base = [
            'enabled'     => $enabled,
            'maintenance' => $maintenance,
            'online'      => false,
            'state'       => 'disabled',
            'label'       => 'Indisponível',
            'api_version' => null,
        ];

        if (!$enabled) {
            return $base;
        }
        if ($maintenance) {
            return array_merge($base, ['state' => 'maintenance', 'label' => 'Manutenção']);
        }

        $health = $this->cachedHealth();
        $online = (bool) ($health['online'] ?? false);

        return array_merge($base, [
            'online'      => $online,
            'state'       => $online ? 'online' : 'offline',
            'label'       => $online ? 'Online' : 'Offline',
            'api_version' => $health['api_version'] ?? null,
        ]);
    }

    /**
     * Health com cache curto. Só chama a API quando o cache expira.
     */
    private function cachedHealth(): array
    {
        $cached = CacheService::get(self::CACHE_KEY, null);
        if (is_array($cached)) {
            return $cached;
        }
        $health = $this->health->check();
        // Guarda apenas o essencial (sem dados sensíveis).
        $slim = [
            'online'      => (bool) $health['online'],
            'api_version' => $health['api_version'] ?? null,
        ];
        CacheService::put(self::CACHE_KEY, $slim, self::TTL);
        return $slim;
    }

    public static function flushCache(): void
    {
        CacheService::forget(self::CACHE_KEY);
    }
}
