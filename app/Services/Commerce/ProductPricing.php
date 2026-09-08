<?php

namespace App\Services\Commerce;

use App\Services\GameApi\GameStoreService;

/**
 * Fonte oficial de preço/moeda: a Game API.
 *
 * O navegador NUNCA informa preço. Este serviço relê o produto ao vivo na Game
 * API (autenticado, para respeitar `owned`/elegibilidade) e devolve um snapshot
 * confiável em CENTAVOS para compor o pedido.
 */
class ProductPricing
{
    private GameStoreService $store;

    public function __construct(?GameStoreService $store = null)
    {
        $this->store = $store ?? new GameStoreService();
    }

    /**
     * Resolve preço/moeda oficiais de um produto. Lança se o produto não for
     * comprável (inexistente, inativo, já possuído ou preço inválido).
     *
     * @return array{
     *   product_id:string, sku:?string, name:string,
     *   unit_price_cents:int, currency:string, owned:?bool, metadata:array
     * }
     *
     * @throws PricingException
     */
    public function resolve(string $productId, ?string $token = null): array
    {
        try {
            $product = $this->store->product($productId, $token);
        } catch (\Throwable $e) {
            throw new PricingException('Não foi possível confirmar o produto na loja do jogo.', 'product_unavailable', $e);
        }

        if (!is_array($product) || $product === []) {
            throw new PricingException('Produto não encontrado.', 'not_found');
        }

        // Produto inativo/indisponível (quando a API informa).
        if (array_key_exists('active', $product) && $product['active'] === false) {
            throw new PricingException('Produto indisponível.', 'inactive');
        }

        // Já possuído: bloqueia recompra quando a API afirma owned=true.
        $owned = $product['owned'] ?? null;
        if ($owned === true) {
            throw new PricingException('Você já possui este item.', 'already_owned');
        }

        $currency = strtoupper((string) ($product['currency'] ?? 'BRL'));
        $unitPriceCents = $this->extractCents($product);
        if ($unitPriceCents <= 0) {
            throw new PricingException('Produto sem preço válido para compra.', 'invalid_price');
        }

        return [
            'product_id'       => (string) ($product['id'] ?? $productId),
            'sku'              => isset($product['sku']) ? (string) $product['sku'] : null,
            'name'             => (string) ($product['name'] ?? $productId),
            'unit_price_cents' => $unitPriceCents,
            'currency'         => $currency !== '' ? substr($currency, 0, 3) : 'BRL',
            'owned'            => is_bool($owned) ? $owned : null,
            'metadata'         => [
                'rarity'       => $product['rarity'] ?? null,
                'entitlements' => $product['entitlements'] ?? [],
            ],
        ];
    }

    /**
     * Extrai o preço em centavos do produto normalizado, aceitando formatos
     * comuns: price_cents (inteiro) ou price (decimal em unidade monetária).
     */
    private function extractCents(array $product): int
    {
        if (isset($product['price_cents']) && is_numeric($product['price_cents'])) {
            return (int) $product['price_cents'];
        }
        if (isset($product['price']) && is_numeric($product['price'])) {
            // Preço decimal (ex.: 49.90) -> centavos com arredondamento seguro.
            return (int) round(((float) $product['price']) * 100);
        }
        return 0;
    }
}
