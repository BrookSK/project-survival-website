<?php

namespace App\Services\GameApi;

use App\Services\CacheService;
use App\Services\GameApi\Exceptions\GameApiException;

/**
 * Loja do jogo: categorias, produtos, detalhe e início de pedido.
 *
 * Catálogo e categorias são públicos e cacheáveis (fallback stale). Quando o
 * jogador está autenticado (token informado), o catálogo é buscado ao vivo,
 * pois cada produto pode trazer `owned` — dado por jogador que NUNCA é
 * cacheado de forma compartilhada.
 *
 * A compra apenas inicia um pedido (status `pending`): a API não cobra e não
 * concede o produto. Este serviço não simula pagamento.
 */
class GameStoreService
{
    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * Categorias da loja (público, cacheável).
     */
    public function categories(): array
    {
        $useCache = GameApiConfig::cacheEnabled();
        $fresh = 'gameapi:store:categories:fresh';
        $stale = 'gameapi:store:categories:stale';

        if ($useCache) {
            $cached = CacheService::get($fresh, null);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $data = $this->client->get('/store/categories');
            $normalized = GameApiAdapter::categories($data);
            if ($useCache) {
                $ttl = GameApiConfig::cacheTtl('categories');
                CacheService::put($fresh, $normalized, $ttl > 0 ? $ttl : 600);
                CacheService::put($stale, $normalized, 0);
            }
            return $normalized;
        } catch (GameApiException $e) {
            if ($useCache) {
                $s = CacheService::get($stale, null);
                if (is_array($s)) {
                    return $s;
                }
            }
            return [];
        }
    }

    /**
     * Catálogo de produtos.
     *
     * @param string|null $token Bearer do jogador. Se presente, busca ao vivo
     *                           (para trazer `owned`) e NÃO usa cache compartilhado.
     */
    public function products(?string $token = null): array
    {
        // Autenticado: sempre ao vivo, sem cache (owned é por jogador).
        if ($token !== null && $token !== '') {
            $data = $this->client->get('/store/products', [], $token);
            return GameApiAdapter::products($data);
        }

        $useCache = GameApiConfig::cacheEnabled();
        $fresh = 'gameapi:store:products:fresh';
        $stale = 'gameapi:store:products:stale';

        if ($useCache) {
            $cached = CacheService::get($fresh, null);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $data = $this->client->get('/store/products');
            $normalized = GameApiAdapter::products($data);
            if ($useCache) {
                $ttl = GameApiConfig::cacheTtl('store');
                CacheService::put($fresh, $normalized, $ttl > 0 ? $ttl : 300);
                CacheService::put($stale, $normalized, 0);
            }
            return $normalized;
        } catch (GameApiException $e) {
            if ($useCache) {
                $s = CacheService::get($stale, null);
                if (is_array($s)) {
                    return $s;
                }
            }
            return [];
        }
    }

    /**
     * Apenas os produtos em destaque (featured), a partir do catálogo público.
     */
    public function featured(int $limit = 4): array
    {
        $featured = array_values(array_filter($this->products(), static fn ($p) => !empty($p['featured'])));
        return array_slice($featured, 0, max(1, $limit));
    }

    /**
     * Detalhe de um produto por id ou sku/slug (público). Pode passar token
     * para receber `owned`. Lança NotFoundException se não existir.
     */
    public function product(string $idOrSku, ?string $token = null): array
    {
        $data = $this->client->get('/store/products/' . rawurlencode($idOrSku), [], $token);
        // A API pode devolver o produto direto ou dentro de data; normaliza.
        if (isset($data['id']) || isset($data['sku']) || isset($data['name'])) {
            return GameApiAdapter::product($data);
        }
        $list = GameApiAdapter::listFrom($data);
        if ($list !== []) {
            return GameApiAdapter::product($list[0]);
        }
        return GameApiAdapter::product(is_array($data) ? $data : []);
    }

    /**
     * Inicia um pedido (autenticado). NÃO cobra e NÃO concede o produto.
     *
     * @return array{order_id:?string, status:string, payment:array, message:string}
     */
    public function purchase(string $token, string $productId): array
    {
        $data = $this->client->post('/store/purchase', ['product_id' => $productId], $token);
        return [
            'order_id' => $data['order_id'] ?? null,
            'status'   => $data['status'] ?? 'pending',
            'payment'  => is_array($data['payment'] ?? null) ? $data['payment'] : [],
            'message'  => $data['message'] ?? '',
        ];
    }

    public static function flushCache(): void
    {
        foreach (['store:products', 'store:categories'] as $n) {
            CacheService::forget('gameapi:' . $n . ':fresh');
            CacheService::forget('gameapi:' . $n . ':stale');
        }
    }
}
