<?php

namespace App\Services\GameApi;

/**
 * Adaptador de formatos da API do jogo.
 *
 * Normaliza o JSON bruto da API em arrays previsíveis para as views, sem
 * inventar campos: apenas garante presença/tipos das chaves do contrato e
 * aplica defaults seguros quando um campo opcional vier ausente. Se a API
 * evoluir com novos campos, eles passam adiante sem quebrar o frontend.
 *
 * Não decide regra de negócio (ex.: `owned` vem sempre da API, nunca daqui).
 */
class GameApiAdapter
{
    /**
     * Normaliza um produto da loja.
     */
    public static function product(array $p): array
    {
        return [
            'id'           => $p['id'] ?? ($p['sku'] ?? ''),
            'sku'          => $p['sku'] ?? '',
            'slug'         => $p['slug'] ?? ($p['sku'] ?? ($p['id'] ?? '')),
            'name'         => $p['name'] ?? 'Item',
            'description'  => $p['description'] ?? '',
            'type'         => $p['type'] ?? '',
            'price'        => isset($p['price']) ? (float) $p['price'] : null,
            'currency'     => $p['currency'] ?? '',
            'image'        => $p['image'] ?? '',
            'banner'       => $p['banner'] ?? '',
            'rarity'       => $p['rarity'] ?? '',
            'featured'     => !empty($p['featured']),
            'entitlements' => is_array($p['entitlements'] ?? null) ? $p['entitlements'] : [],
            // owned só é confiável quando a API o inclui (autenticado); caso
            // contrário fica null (desconhecido) — nunca inferido localmente.
            'owned'        => array_key_exists('owned', $p) ? (bool) $p['owned'] : null,
        ];
    }

    /**
     * Normaliza uma lista de produtos.
     */
    public static function products($data): array
    {
        $list = self::listFrom($data);
        return array_map([self::class, 'product'], $list);
    }

    /**
     * Normaliza uma categoria da loja (campos flexíveis conforme a API).
     */
    public static function category(array $c): array
    {
        return [
            'id'    => $c['id'] ?? ($c['slug'] ?? ''),
            'slug'  => $c['slug'] ?? ($c['id'] ?? ''),
            'name'  => $c['name'] ?? ($c['label'] ?? 'Categoria'),
        ];
    }

    public static function categories($data): array
    {
        return array_map([self::class, 'category'], self::listFrom($data));
    }

    /**
     * Normaliza uma notícia (campos comuns; preserva o que vier).
     */
    public static function news(array $n): array
    {
        return [
            'id'           => $n['id'] ?? ($n['slug'] ?? ''),
            'slug'         => $n['slug'] ?? '',
            'title'        => $n['title'] ?? '',
            'excerpt'      => $n['excerpt'] ?? ($n['summary'] ?? ''),
            'body'         => $n['body'] ?? ($n['content'] ?? ''),
            'image'        => $n['image'] ?? ($n['cover'] ?? ''),
            'url'          => $n['url'] ?? '',
            'published_at' => $n['published_at'] ?? ($n['date'] ?? null),
        ];
    }

    public static function newsList($data): array
    {
        return array_map([self::class, 'news'], self::listFrom($data));
    }

    /**
     * Normaliza um evento ativo.
     */
    public static function event(array $e): array
    {
        return [
            'id'          => $e['id'] ?? ($e['slug'] ?? ''),
            'slug'        => $e['slug'] ?? '',
            'title'       => $e['title'] ?? ($e['name'] ?? ''),
            'description' => $e['description'] ?? ($e['summary'] ?? ''),
            'image'       => $e['image'] ?? ($e['banner'] ?? ''),
            'starts_at'   => $e['starts_at'] ?? ($e['start'] ?? null),
            'ends_at'     => $e['ends_at'] ?? ($e['end'] ?? null),
        ];
    }

    public static function events($data): array
    {
        return array_map([self::class, 'event'], self::listFrom($data));
    }

    /**
     * Extrai uma lista de itens de formatos comuns: array direto, ou
     * envelope com chaves data/items/results/<recurso>.
     */
    public static function listFrom($data): array
    {
        if (is_array($data)) {
            // Lista sequencial direta.
            if ($data === [] || array_keys($data) === range(0, count($data) - 1)) {
                return $data;
            }
            foreach (['data', 'items', 'results', 'products', 'categories', 'news', 'events'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $data[$key];
                }
            }
        }
        return [];
    }
}
