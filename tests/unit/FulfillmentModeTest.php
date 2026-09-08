<?php

use App\Core\Database;
use App\Services\Commerce\NullFulfillmentAdapter;
use App\Services\SettingsService;

/**
 * Modo de fulfillment OFICIAL (game_webhook): o site NÃO concede itens.
 * A concessão é feita pela Game API via webhook do provedor de pagamento.
 */
class FulfillmentModeTest extends TestCase
{
    private function boot(): void
    {
        $db = new StubDatabase();
        $db->setSettings([]); // fulfillment_mode default = game_webhook
        Database::setInstance($db);
        SettingsService::flush();
    }

    public function testNullAdapterModeIsGameWebhook(): void
    {
        $this->boot();
        $this->assertEquals('game_webhook', (new NullFulfillmentAdapter())->mode());
    }

    public function testNullAdapterDoesNotGrantAndDelegates(): void
    {
        $this->boot();

        $orders = new FakeOrder();
        $orderId = $orders->seed([
            'reference' => 'SITE-000100', 'player_id' => 'p1', 'currency' => 'BRL',
            'total_cents' => 4990, 'fulfillment_status' => 'pending',
        ]);
        $order = $orders->find($orderId);
        $items = [['id' => 1, 'product_id' => 'prod_1', 'sku' => 'gold', 'quantity' => 1]];
        $events = new FakeOrderEvent();

        $adapter = new NullFulfillmentAdapter($orders, $events);
        $adapter->fulfillOrder($order, $items);

        // NÃO concede: apenas delega e marca processing (a Game API concede via webhook).
        $this->assertEquals(1, $events->countOfType('fulfillment.delegated'), 'registra delegação, não concessão');
        $this->assertEquals('processing', $orders->find($orderId)['fulfillment_status']);
    }
}
