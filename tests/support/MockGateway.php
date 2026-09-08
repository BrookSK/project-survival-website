<?php

use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentIntent;
use App\Services\Payments\PaymentStatus;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookResult;

/**
 * Gateway de teste: respostas programáveis, sem tocar em rede/gateway real.
 * Permite provar o fluxo do PaymentService/WebhookProcessor de forma
 * determinística. Vive apenas em tests/.
 */
class MockGateway implements PaymentGatewayInterface
{
    public string $statusToReturn = PaymentStatus::APPROVED;
    public int $getPaymentCalls = 0;
    public int $refundCalls = 0;
    public array $webhookResult;

    public function __construct(?array $webhookResult = null)
    {
        // Por padrão, um webhook válido e acionável.
        $this->webhookResult = $webhookResult ?? [
            'signatureValid' => true,
            'eventId' => 'evt_1',
            'eventType' => 'payment.updated',
            'externalPaymentId' => 'mp_123',
        ];
    }

    public function name(): string
    {
        return 'mock';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function createPayment(array $order, string $method, array $options = []): PaymentIntent
    {
        return new PaymentIntent('mp_123', PaymentStatus::PENDING, $method, (int) ($order['total_cents'] ?? 0), (string) ($order['currency'] ?? 'BRL'));
    }

    public function getPayment(string $externalId): PaymentIntent
    {
        $this->getPaymentCalls++;
        return new PaymentIntent($externalId, $this->statusToReturn, 'pix', 4990, 'BRL');
    }

    public function refundPayment(string $externalId, ?int $amountCents = null): RefundResult
    {
        $this->refundCalls++;
        return new RefundResult($externalId, 'refunded', $amountCents ?? 4990);
    }

    public function parseWebhook(array $headers, string $rawBody, array $query = []): WebhookResult
    {
        return new WebhookResult(
            (bool) $this->webhookResult['signatureValid'],
            $this->webhookResult['eventId'] ?? null,
            $this->webhookResult['eventType'] ?? null,
            $this->webhookResult['externalPaymentId'] ?? null
        );
    }
}
