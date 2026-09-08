<?php

use App\Services\Commerce\PricingException;
use App\Services\Commerce\ProductPricing;
use App\Services\GameApi\GameStoreService;

/**
 * ProductPricing: o preço/moeda vêm da Game API (nunca do navegador). Produto
 * inválido/owned/sem preço bloqueia a compra.
 */
class ProductPricingTest extends TestCase
{
    private function pricingReturning(array $product): ProductPricing
    {
        $store = new class($product) extends GameStoreService {
            private array $p;
            public function __construct(array $p)
            {
                $this->p = $p; // não chama parent (sem client real)
            }
            public function product(string $idOrSku, ?string $token = null): array
            {
                return $this->p;
            }
        };
        return new ProductPricing($store);
    }

    public function testUsesOfficialPriceInCents(): void
    {
        $p = $this->pricingReturning(['id' => 'prod_1', 'name' => 'Ouro', 'price' => 49.90, 'currency' => 'BRL']);
        $resolved = $p->resolve('prod_1', 'token');
        // O preço vem da API (49.90 -> 4990 centavos), não do navegador.
        $this->assertEquals(4990, $resolved['unit_price_cents']);
        $this->assertEquals('BRL', $resolved['currency']);
        $this->assertEquals('prod_1', $resolved['product_id']);
    }

    public function testPriceCentsFieldIsPreferred(): void
    {
        $p = $this->pricingReturning(['id' => 'prod_1', 'name' => 'Ouro', 'price_cents' => 1000, 'currency' => 'BRL']);
        $this->assertEquals(1000, $p->resolve('prod_1', 'token')['unit_price_cents']);
    }

    public function testOwnedProductIsBlocked(): void
    {
        $p = $this->pricingReturning(['id' => 'prod_1', 'name' => 'Ouro', 'price' => 10.0, 'owned' => true]);
        try {
            $p->resolve('prod_1', 'token');
            $this->assertTrue(false, 'deveria lançar para produto já possuído');
        } catch (PricingException $e) {
            $this->assertEquals('already_owned', $e->reason);
        }
    }

    public function testInvalidPriceIsBlocked(): void
    {
        $p = $this->pricingReturning(['id' => 'prod_1', 'name' => 'Ouro', 'price' => 0]);
        try {
            $p->resolve('prod_1', 'token');
            $this->assertTrue(false, 'deveria lançar para preço inválido');
        } catch (PricingException $e) {
            $this->assertEquals('invalid_price', $e->reason);
        }
    }

    public function testEmptyProductIsNotFound(): void
    {
        $p = $this->pricingReturning([]);
        try {
            $p->resolve('prod_x', 'token');
            $this->assertTrue(false, 'deveria lançar not_found');
        } catch (PricingException $e) {
            $this->assertEquals('not_found', $e->reason);
        }
    }
}
