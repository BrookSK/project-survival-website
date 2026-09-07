<?php

use App\Services\GameApi\GameApiAdapter;

/**
 * Testa a normalização de dados da API (Adapter).
 */
class GameApiAdapterTest extends TestCase
{
    public function testProductNormalizesFields(): void
    {
        $p = GameApiAdapter::product([
            'id' => 'p1', 'sku' => 'SKU1', 'name' => 'Skin X', 'type' => 'skin',
            'price' => '9.90', 'currency' => 'BRL', 'featured' => 1, 'owned' => true,
        ]);
        $this->assertEquals('p1', $p['id'], 'id preservado');
        $this->assertEquals(9.9, $p['price'], 'preço vira float');
        $this->assertTrue($p['featured'], 'featured vira bool');
        $this->assertTrue($p['owned'] === true, 'owned true quando presente');
    }

    public function testOwnedNullWhenAbsent(): void
    {
        $p = GameApiAdapter::product(['id' => 'p2', 'name' => 'Y']);
        $this->assertTrue($p['owned'] === null, 'owned deve ser null (desconhecido) quando ausente — nunca inferido');
    }

    public function testListFromDirectArray(): void
    {
        $list = GameApiAdapter::listFrom([['id' => 1], ['id' => 2]]);
        $this->assertEquals(2, count($list), 'lista sequencial direta');
    }

    public function testListFromEnvelope(): void
    {
        $list = GameApiAdapter::listFrom(['products' => [['id' => 1]]]);
        $this->assertEquals(1, count($list), 'extrai de chave products');
    }

    public function testListFromEmptyOnUnknown(): void
    {
        $this->assertEquals([], GameApiAdapter::listFrom(['foo' => 'bar']), 'sem lista conhecida vira []');
    }

    public function testProductsMapsAll(): void
    {
        $products = GameApiAdapter::products([['id' => 'a', 'name' => 'A'], ['id' => 'b', 'name' => 'B']]);
        $this->assertEquals(2, count($products), 'mapeia todos');
        $this->assertEquals('A', $products[0]['name'], 'nome normalizado');
    }

    public function testEventNormalization(): void
    {
        $ev = GameApiAdapter::event(['id' => 'e1', 'title' => 'Halloween', 'ends_at' => '2026-10-31']);
        $this->assertEquals('Halloween', $ev['title'], 'título do evento');
        $this->assertEquals('2026-10-31', $ev['ends_at'], 'ends_at preservado');
    }
}
