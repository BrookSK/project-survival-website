<?php

namespace App\Services\GameApi;

use App\Services\CacheService;
use App\Services\GameApi\Exceptions\GameApiException;

/**
 * Consome as informações OFICIAIS de release/download da Game API.
 *
 * Endpoints (públicos, sem token) — contrato oficial do jogo:
 *   - GET /public/releases/latest        (canal stable)
 *   - GET /public/releases/{channel}      (stable|beta|dev)
 *   - GET /public/download{/channel}      (acrescenta download_url)
 *
 * O site NUNCA é autoridade sobre versão/hash/tamanho: apenas consome e
 * cacheia. Se a API estiver indisponível, usa o último valor cacheado (stale)
 * como fallback e nunca quebra a página. Nada é inventado.
 */
class GameReleaseService
{
    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * Release do canal público padrão (stable), pronta para download.
     * Usa `/public/download` (traz `download_url`). Retorna null se não houver
     * release utilizável e não houver cache stale.
     */
    public function latest(): ?ReleaseInformation
    {
        return $this->forChannel(GameApiConfig::releaseChannel());
    }

    /**
     * Release de um canal específico (stable|beta|dev).
     */
    public function forChannel(string $channel): ?ReleaseInformation
    {
        $channel = in_array($channel, ['stable', 'beta', 'dev'], true) ? $channel : 'stable';

        $useCache = GameApiConfig::cacheEnabled();
        $fresh = 'gameapi:release:' . $channel . ':fresh';
        $stale = 'gameapi:release:' . $channel . ':stale';

        if ($useCache) {
            $cached = CacheService::get($fresh, null);
            if (is_array($cached)) {
                return ReleaseInformation::fromApi($cached);
            }
        }

        try {
            // `/public/download/{channel}` inclui `download_url` pronto para o botão.
            $data = $this->client->get('/public/download/' . rawurlencode($channel));
            if (!is_array($data)) {
                return $this->stale($stale);
            }
            $release = ReleaseInformation::fromApi($data);

            if ($useCache && $release->isUsable()) {
                $ttl = GameApiConfig::releaseCacheTtl();
                CacheService::put($fresh, $release->toArray(), $ttl > 0 ? $ttl : 900);
                // Stale nunca expira: fallback quando a API cair.
                CacheService::put($stale, $release->toArray(), 0);
            }
            return $release;
        } catch (GameApiException $e) {
            // Indisponível/erro: cai para o último valor conhecido, se houver.
            return $this->stale($stale);
        }
    }

    /**
     * Retorna a release stale (fallback) de um canal, ou null.
     */
    private function stale(string $staleKey): ?ReleaseInformation
    {
        $s = CacheService::get($staleKey, null);
        return is_array($s) ? ReleaseInformation::fromApi($s) : null;
    }

    /**
     * Indica se a informação servida veio do cache stale (API indisponível).
     * Útil para a UI avisar que os dados podem estar defasados.
     */
    public function isServingStale(string $channel): bool
    {
        $channel = in_array($channel, ['stable', 'beta', 'dev'], true) ? $channel : 'stable';
        return !CacheService::has('gameapi:release:' . $channel . ':fresh')
            && CacheService::has('gameapi:release:' . $channel . ':stale');
    }

    /**
     * Limpa o cache de release (todos os canais).
     */
    public static function flushCache(): void
    {
        foreach (['stable', 'beta', 'dev'] as $c) {
            CacheService::forget('gameapi:release:' . $c . ':fresh');
            CacheService::forget('gameapi:release:' . $c . ':stale');
        }
    }
}
