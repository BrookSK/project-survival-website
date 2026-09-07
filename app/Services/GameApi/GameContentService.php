<?php

namespace App\Services\GameApi;

use App\Core\Logger;
use App\Services\CacheService;
use App\Services\GameApi\Exceptions\ApiUnavailableException;
use App\Services\GameApi\Exceptions\GameApiException;

/**
 * Conteúdo remoto público do jogo: notícias (GET /news) e eventos (GET /events).
 *
 * Estratégia offline-first: cada recurso tem uma cópia "fresh" (com TTL curto)
 * e uma cópia "stale" persistente. A leitura tenta a fresh; se expirada,
 * consulta a API e atualiza ambas. Se a API estiver indisponível, cai para a
 * stale (última boa resposta), de modo que a Home/páginas não quebrem.
 *
 * São dados públicos (iguais para todos) — o cache é compartilhado com
 * segurança. Nada de dados de jogador aqui.
 */
class GameContentService
{
    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * Notícias do jogo, normalizadas. Limite opcional aplicado após a leitura.
     */
    public function news(?int $limit = null): array
    {
        $items = $this->cachedList('news', '/news', GameApiConfig::cacheTtl('news'), [GameApiAdapter::class, 'newsList']);
        if ($limit !== null && $limit > 0) {
            return array_slice($items, 0, $limit);
        }
        return $items;
    }

    /**
     * Eventos ativos, normalizados.
     */
    public function events(): array
    {
        return $this->cachedList('events', '/events', GameApiConfig::cacheTtl('events'), [GameApiAdapter::class, 'events']);
    }

    /**
     * Lê um recurso público com cache fresh + fallback stale.
     *
     * @param callable $normalizer  função que normaliza a resposta crua
     */
    private function cachedList(string $name, string $path, int $ttl, callable $normalizer): array
    {
        $useCache = GameApiConfig::cacheEnabled();
        $freshKey = 'gameapi:' . $name . ':fresh';
        $staleKey = 'gameapi:' . $name . ':stale';

        // 1) Cache fresh válido.
        if ($useCache) {
            $sentinel = self::class;
            $fresh = CacheService::get($freshKey, $sentinel);
            if ($fresh !== $sentinel && is_array($fresh)) {
                return $fresh;
            }
        }

        // 2) Consulta a API.
        try {
            if (!GameApiConfig::isEnabled()) {
                throw new ApiUnavailableException('Integração desativada.', 'INTEGRATION_DISABLED');
            }
            $raw = $this->client->get($path);
            $normalized = $normalizer($raw);

            if ($useCache) {
                CacheService::put($freshKey, $normalized, $ttl > 0 ? $ttl : 300);
                CacheService::put($staleKey, $normalized, 0); // persistente p/ fallback
            }
            return $normalized;
        } catch (ApiUnavailableException $e) {
            // 3) Fallback para a última resposta conhecida.
            if ($useCache) {
                $stale = CacheService::get($staleKey, null);
                if (is_array($stale)) {
                    Logger::warning('game_api.content.fallback_stale', ['resource' => $name]);
                    return $stale;
                }
            }
            // Sem cache disponível: lista vazia (a view mostra estado offline/empty).
            Logger::warning('game_api.content.unavailable', ['resource' => $name]);
            return [];
        } catch (GameApiException $e) {
            Logger::warning('game_api.content.error', ['resource' => $name, 'code' => $e->apiCode()]);
            if ($useCache) {
                $stale = CacheService::get($staleKey, null);
                if (is_array($stale)) {
                    return $stale;
                }
            }
            return [];
        }
    }

    /**
     * Invalida o cache de um recurso (news|events) ou de ambos.
     */
    public static function flushCache(?string $name = null): void
    {
        $names = $name ? [$name] : ['news', 'events'];
        foreach ($names as $n) {
            CacheService::forget('gameapi:' . $n . ':fresh');
            CacheService::forget('gameapi:' . $n . ':stale');
        }
    }
}
