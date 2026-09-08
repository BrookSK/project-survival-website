<?php

use App\Core\Database;
use App\Services\Commerce\GameFulfillmentService;
use App\Services\SettingsService;

/**
 * Idempotência da concessão (fulfillment) via adapter da Game API.
 *
 * Usa MockHttpClient (Game API mockada) + mocks in-memory dos Models. Prova que
 * reenvios NÃO duplicam a concessão e que a Game API offline mantém pending.
 */
class FulfillmentIdempotencyTest extends TestCase
{
    private function bootConfigured(): StubDatabase
    {
        $db = new StubDatabase();
        // Integração habilitada + service account configurada.
        $db->setSettings([
            'game_api_enabled'            => ['value' => '1', 'type' => 'boolean', 'group' => 'game_api'],
            'game_api_base_url'           => ['value' => 'https://api.jogo.example/api/v1', 'group' => 'game_api'],
            'game_api_service_client_id'  => ['value' => 'site-service', 'group' => 'payments'],
            'game_api_service_secret'     => ['value' => 'segredo', 'group' => 'payments'],
            'fulfillment_max_attempts'    => ['value' => '8', 'type' => 'integer', 'group' => 'payments'],
        ]);
        Database::setInstance($db);
        SettingsService::flush();
        return $db;
    }

    private function order(FakeOrder $orders): array
    {
        $id = $orders->seed([
            'reference' => 'SITE-000001', 'player_id' => 'player_1', 'currency' => 'BRL',
            'total_cents' => 4990, 'payment_status' => 'approved', 'fulfillment_status' => 'pending',
            'payment_reference' => 'mp_123',
        ]);
        $order = $orders->find($id);
        $order['__items'] = [['id' => 1, 'product_id' => 'prod_1', 'sku' => 'gold', 'quantity' => 1, 'line_total_cents' => 4990]];
        $orders->rows[$id] = $order;
        return $order;
    }

    public function testGrantSucceedsOnce(): void
    {
        $this->bootConfigured();
        $http = new MockHttpClient();
        // A Game API concede com sucesso.
        $http->pushJson(200, ['status' => 'granted', 'fulfillment_id' => 'ff_1']);

        $orders = new FakeOrder();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();
        $order = $this->order($orders);

        $svc = new GameFulfillmentService($http, $ff, $orders, $events);
        $svc->fulfillOrder($order, $order['__items']);

        $this->assertEquals(1, $ff->countFulfilled(), 'deveria haver 1 fulfillment concluído');
        $this->assertEquals(1, $ff->countAll(), 'deveria haver 1 registro de fulfillment');
        $this->assertEquals('fulfilled', $orders->find($order['id'])['fulfillment_status']);
    }

    public function testRepeatedFulfillDoesNotDuplicate(): void
    {
        $this->bootConfigured();
        $http = new MockHttpClient();
        // Primeira concessão OK; chamadas seguintes nem deveriam ocorrer (já fulfilled).
        $http->pushJson(200, ['status' => 'granted', 'fulfillment_id' => 'ff_1']);
        $http->pushJson(200, ['status' => 'granted', 'fulfillment_id' => 'ff_1', 'idempotent_replay' => true]);

        $orders = new FakeOrder();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();
        $order = $this->order($orders);

        $svc = new GameFulfillmentService($http, $ff, $orders, $events);
        // 3 tentativas seguidas (simula retries/reenvios).
        $svc->fulfillOrder($order, $order['__items']);
        $svc->fulfillOrder($order, $order['__items']);
        $svc->fulfillOrder($order, $order['__items']);

        $this->assertEquals(1, $ff->countAll(), '1 fulfillment lógico mesmo após 3 tentativas');
        $this->assertEquals(1, $ff->countFulfilled(), '1 concessão mesmo após 3 tentativas');
    }

    public function testGameApiOfflineKeepsPending(): void
    {
        $this->bootConfigured();
        $http = new MockHttpClient();
        $http->pushTransportError('timeout'); // Game API indisponível

        $orders = new FakeOrder();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();
        $order = $this->order($orders);

        $svc = new GameFulfillmentService($http, $ff, $orders, $events);
        $svc->fulfillOrder($order, $order['__items']);

        $row = $ff->findByKey('SITE-000001:prod_1');
        $this->assertEquals('pending', $row['status'], 'API offline -> pending (retry)');
        $this->assertEquals(0, $ff->countFulfilled(), 'NUNCA concede quando a API está offline');
        $this->assertEquals('pending', $orders->find($order['id'])['fulfillment_status']);
    }

    public function testPermanentFailureDoesNotRetry(): void
    {
        $this->bootConfigured();
        $http = new MockHttpClient();
        $http->pushJson(404, ['error' => ['message' => 'produto inexistente']]);

        $orders = new FakeOrder();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();
        $order = $this->order($orders);

        $svc = new GameFulfillmentService($http, $ff, $orders, $events);
        $svc->fulfillOrder($order, $order['__items']);

        $row = $ff->findByKey('SITE-000001:prod_1');
        $this->assertEquals('failed', $row['status'], '404 -> falha definitiva');
        $this->assertEquals(1, (int) $row['permanent_failure'], 'falha permanente (sem retry)');
    }

    public function testNotConfiguredKeepsPendingWithoutCalling(): void
    {
        // Sem service account configurada -> nunca chama, mantém pending.
        $db = new StubDatabase();
        $db->setSettings([]); // vazio
        Database::setInstance($db);
        SettingsService::flush();

        $http = new MockHttpClient();
        $orders = new FakeOrder();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();
        $order = $this->order($orders);

        $svc = new GameFulfillmentService($http, $ff, $orders, $events);
        $svc->fulfillOrder($order, $order['__items']);

        $this->assertEquals(0, count($http->calls), 'não deve chamar a Game API sem configuração');
        $this->assertEquals('pending', $ff->findByKey('SITE-000001:prod_1')['status']);
    }
}
