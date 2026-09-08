<?php

use App\Core\Database;
use App\Services\Commerce\GameFulfillmentService;
use App\Services\Commerce\WebhookProcessor;
use App\Services\Payments\PaymentService;
use App\Services\SettingsService;

/**
 * TESTE CRÍTICO (Prompt 6): 10 webhooks idênticos + 3 retries devem resultar em
 * 1 pedido / 1 pagamento lógico / 1 fulfillment / 1 entitlement.
 *
 * Tudo mockado explicitamente: gateway (MockGateway), Game API (MockHttpClient),
 * Models (mocks in-memory que simulam as UNIQUE keys). Sem rede, sem DB real,
 * sem simular pagamento — o "aprovado" vem do gateway mockado (fonte de verdade).
 */
class WebhookCriticalTest extends TestCase
{
    private function boot(): void
    {
        $db = new StubDatabase();
        $db->setSettings([
            'game_api_enabled'           => ['value' => '1', 'type' => 'boolean', 'group' => 'game_api'],
            'game_api_base_url'          => ['value' => 'https://api.jogo.example/api/v1', 'group' => 'game_api'],
            'game_api_service_client_id' => ['value' => 'site-service', 'group' => 'payments'],
            'game_api_service_secret'    => ['value' => 'segredo', 'group' => 'payments'],
            'fulfillment_max_attempts'   => ['value' => '8', 'type' => 'integer', 'group' => 'payments'],
        ]);
        Database::setInstance($db);
        SettingsService::flush();
    }

    public function testTenWebhooksPlusRetriesYieldSingleEffect(): void
    {
        $this->boot();

        // --- 1 pedido pré-existente (aguardando pagamento) com 1 transação ---
        $orders = new FakeOrder();
        $orderId = $orders->seed([
            'reference' => 'SITE-000042', 'player_id' => 'player_9', 'currency' => 'BRL',
            'total_cents' => 4990, 'order_status' => 'awaiting_payment',
            'payment_status' => 'pending', 'fulfillment_status' => 'none',
        ]);
        $order = $orders->find($orderId);
        $order['__items'] = [['id' => 1, 'product_id' => 'prod_1', 'sku' => 'gold', 'quantity' => 1, 'line_total_cents' => 4990]];
        $orders->rows[$orderId] = $order;

        $tx = new FakePaymentTransaction();
        $tx->seed(['order_id' => $orderId, 'gateway' => 'mock', 'external_id' => 'mp_123', 'status' => 'pending', 'amount_cents' => 4990, 'currency' => 'BRL']);

        $webhooks = new FakeWebhookEvent();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();

        // Gateway confirma "approved" (fonte de verdade). Game API concede.
        $gateway = new MockGateway();
        $payments = new PaymentService($gateway, null, $orders, $tx, $events);

        $ffHttp = new MockHttpClient();
        // Enfileira várias concessões OK (só a 1ª efetiva; depois já está fulfilled).
        for ($i = 0; $i < 15; $i++) {
            $ffHttp->pushJson(200, ['status' => 'granted', 'fulfillment_id' => 'ff_1', 'idempotent_replay' => $i > 0]);
        }
        $fulfillment = new GameFulfillmentService($ffHttp, $ff, $orders, $events);

        $processor = new WebhookProcessor($payments, $fulfillment, $webhooks, $orders, $tx, $events);

        // Mesmo evento (event_id 'evt_1') chegando 10x + 3 "retries" = 13 entregas idênticas.
        $headers = [];
        $body = json_encode(['id' => 'evt_1', 'type' => 'payment', 'data' => ['id' => 'mp_123']]);

        $processedFirst = null;
        $duplicates = 0;
        for ($i = 0; $i < 13; $i++) {
            $result = $processor->process('mock', $headers, $body, []);
            if ($i === 0) {
                $processedFirst = $result;
            } elseif ($result === 'duplicate') {
                $duplicates++;
            }
        }

        // 1 processamento efetivo; os demais 12 são idempotentes (duplicate).
        $this->assertEquals('processed', $processedFirst, 'o 1º webhook deve ser processado');
        $this->assertEquals(12, $duplicates, 'os 12 reenvios devem ser idempotentes (duplicate)');

        // 1 evento de webhook registrado (UNIQUE provider+event_id).
        $this->assertEquals(1, $webhooks->countAll(), '1 webhook_event lógico');

        // 1 fulfillment e 1 concessão (entitlement) — nunca duplica.
        $this->assertEquals(1, $ff->countAll(), '1 fulfillment lógico');
        $this->assertEquals(1, $ff->countFulfilled(), '1 concessão (entitlement) — não duplicada');

        // Pedido: pago e entregue.
        $final = $orders->find($orderId);
        $this->assertEquals('approved', $final['payment_status']);
        $this->assertEquals('fulfilled', $final['fulfillment_status']);
    }

    public function testInvalidSignatureNeverProcesses(): void
    {
        $this->boot();

        $orders = new FakeOrder();
        $tx = new FakePaymentTransaction();
        $webhooks = new FakeWebhookEvent();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();

        // Gateway devolve assinatura inválida.
        $gateway = new MockGateway(['signatureValid' => false, 'eventId' => 'evt_x', 'externalPaymentId' => 'mp_x']);
        $payments = new PaymentService($gateway, null, $orders, $tx, $events);
        $fulfillment = new GameFulfillmentService(new MockHttpClient(), $ff, $orders, $events);
        $processor = new WebhookProcessor($payments, $fulfillment, $webhooks, $orders, $tx, $events);

        $result = $processor->process('mock', [], '{}', []);

        $this->assertEquals('invalid', $result, 'assinatura inválida deve ser rejeitada');
        $this->assertEquals(0, $ff->countFulfilled(), 'nada é concedido com assinatura inválida');
    }

    public function testUnknownTransactionIsIgnored(): void
    {
        $this->boot();

        $orders = new FakeOrder();
        $tx = new FakePaymentTransaction(); // vazio -> transação desconhecida
        $webhooks = new FakeWebhookEvent();
        $ff = new FakeFulfillment();
        $events = new FakeOrderEvent();

        $gateway = new MockGateway();
        $payments = new PaymentService($gateway, null, $orders, $tx, $events);
        $fulfillment = new GameFulfillmentService(new MockHttpClient(), $ff, $orders, $events);
        $processor = new WebhookProcessor($payments, $fulfillment, $webhooks, $orders, $tx, $events);

        $body = json_encode(['id' => 'evt_2', 'data' => ['id' => 'mp_999']]);
        $result = $processor->process('mock', [], $body, []);

        $this->assertEquals('ignored', $result, 'transação desconhecida -> ignorado (nunca concede)');
        $this->assertEquals(0, $ff->countFulfilled());
    }
}
