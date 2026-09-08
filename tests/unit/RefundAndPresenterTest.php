<?php

use App\Core\Database;
use App\Services\Commerce\OrderPresenter;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaymentStatus;
use App\Services\SettingsService;

/**
 * Refund via gateway (MockGateway) + rótulos/formatação do OrderPresenter.
 */
class RefundAndPresenterTest extends TestCase
{
    private function boot(): void
    {
        $db = new StubDatabase();
        $db->setSettings([]);
        Database::setInstance($db);
        SettingsService::flush();
    }

    public function testRefundCallsGatewayAndUpdatesTransaction(): void
    {
        $this->boot();

        $orders = new FakeOrder();
        $orderId = $orders->seed([
            'reference' => 'SITE-000007', 'player_id' => 'p1', 'currency' => 'BRL',
            'total_cents' => 4990, 'payment_status' => 'approved',
        ]);
        $tx = new FakePaymentTransaction();
        $tx->seed(['order_id' => $orderId, 'gateway' => 'mock', 'external_id' => 'mp_123', 'status' => 'approved', 'amount_cents' => 4990, 'currency' => 'BRL']);
        $events = new FakeOrderEvent();

        $gateway = new MockGateway();
        $payments = new PaymentService($gateway, null, $orders, $tx, $events);

        $result = $payments->refund($orderId, 'mp_123');

        $this->assertEquals(1, $gateway->refundCalls, 'refund deve chamar o gateway (nunca só status local)');
        $this->assertEquals('refunded', $tx->findByExternal('mock', 'mp_123')['status']);
        $this->assertEquals(1, $events->countOfType('refund.done'));
    }

    public function testSyncFromGatewayReflectsChargeback(): void
    {
        $this->boot();

        $orders = new FakeOrder();
        $orderId = $orders->seed([
            'reference' => 'SITE-000008', 'player_id' => 'p1', 'currency' => 'BRL',
            'total_cents' => 4990, 'payment_status' => 'approved', 'order_status' => 'paid',
        ]);
        $tx = new FakePaymentTransaction();
        $tx->seed(['order_id' => $orderId, 'gateway' => 'mock', 'external_id' => 'mp_123', 'status' => 'approved', 'amount_cents' => 4990, 'currency' => 'BRL']);
        $events = new FakeOrderEvent();

        $gateway = new MockGateway();
        $gateway->statusToReturn = PaymentStatus::CHARGED_BACK;
        $payments = new PaymentService($gateway, null, $orders, $tx, $events);

        $payments->syncFromGateway($orderId, 'mp_123');

        // O status vem do gateway (fonte de verdade).
        $this->assertEquals('charged_back', $orders->find($orderId)['payment_status']);
        $this->assertEquals('refunded', $orders->find($orderId)['order_status']);
    }

    public function testPresenterFormatsMoneyInCents(): void
    {
        $this->assertEquals('BRL 49,90', OrderPresenter::money(4990));
        $this->assertEquals('BRL 1.234,56', OrderPresenter::money(123456));
    }

    public function testPresenterBadgesAndLabels(): void
    {
        $this->assertEquals('success', OrderPresenter::badge('approved'));
        $this->assertEquals('danger', OrderPresenter::badge('rejected'));
        $this->assertEquals('Entregue', OrderPresenter::fulfillmentLabel('fulfilled'));
        $this->assertEquals('Aprovado', OrderPresenter::paymentLabel('approved'));
    }
}
