<?php

namespace App\Services\GameApi;

use App\Services\CacheService;
use App\Services\GameApi\Exceptions\GameApiException;

/**
 * Configuração remota do jogo (GET /game/config).
 *
 * Expõe `config`, `feature_flags`, `min_supported_version` e `content_version`
 * conforme o contrato. Cacheado (público) com fallback stale. As feature flags
 * do jogo NÃO controlam funcionalidades administrativas do site — são apenas
 * leitura informativa (ex.: exibir versão do jogo).
 */
class GameConfigService
{
    private const FRESH_KEY = 'gameapi:config:fresh';
    private const STALE_KEY = 'gameapi:config:stale';

    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * Retorna a config completa normalizada (com defaults seguros).
     *
     * @return array{config:array, feature_flags:array, min_supported_version:?string, content_version:?string}
     */
    public function all(): array
    {
        $default = [
            'config'                => [],
            'feature_flags'         => [],
            'min_supported_version' => null,
            'content_version'       => null,
        ];

        $useCache = GameApiConfig::cacheEnabled();

        if ($useCache) {
            $cached = CacheService::get(self::FRESH_KEY, null);
            if (is_array($cached)) {
                return array_merge($default, $cached);
            }
        }

        if (!GameApiConfig::isEnabled()) {
            return $default;
        }

        try {
            $data = $this->client->get('/game/config');
            $normalized = [
                'config'                => is_array($data['config'] ?? null) ? $data['config'] : [],
                'feature_flags'         => is_array($data['feature_flags'] ?? null) ? $data['feature_flags'] : [],
                'min_supported_version' => $data['min_supported_version'] ?? null,
                'content_version'       => $data['content_version'] ?? null,
            ];
            if ($useCache) {
                $ttl = GameApiConfig::cacheTtl('config');
                CacheService::put(self::FRESH_KEY, $normalized, $ttl > 0 ? $ttl : 600);
                CacheService::put(self::STALE_KEY, $normalized, 0);
            }
            return $normalized;
        } catch (GameApiException $e) {
            if ($useCache) {
                $stale = CacheService::get(self::STALE_KEY, null);
                if (is_array($stale)) {
                    return array_merge($default, $stale);
                }
            }
            return $default;
        }
    }

    /**
     * Valor de uma feature flag (somente leitura informativa).
     */
    public function flag(string $key, $default = null)
    {
        $flags = $this->all()['feature_flags'];
        return $flags[$key] ?? $default;
    }

    /**
     * Versão de conteúdo do jogo (para exibição pública, quando adequado).
     */
    public function contentVersion(): ?string
    {
        return $this->all()['content_version'];
    }

    public static function flushCache(): void
    {
        CacheService::forget(self::FRESH_KEY);
        CacheService::forget(self::STALE_KEY);
    }
}
