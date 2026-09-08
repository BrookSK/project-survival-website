<?php

namespace App\Services\Payments;

/**
 * Gateway "nenhum": usado quando a loja está desativada ou nenhum provedor foi
 * configurado. Nunca cria pagamento — recusa de forma explícita e segura, sem
 * jamais simular aprovação.
 */
class NullGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'null';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function createPayment(array $order, string $method, array $options = []): PaymentIntent
    {
        throw PaymentException::permanent('Nenhum gateway de pagamento está configurado.');
    }

    public function getPayment(string $externalId): PaymentIntent
    {
        throw PaymentException::permanent('Nenhum gateway de pagamento está configurado.');
    }

    public function refundPayment(string $externalId, ?int $amountCents = null): RefundResult
    {
        throw PaymentException::permanent('Nenhum gateway de pagamento está configurado.');
    }

    public function parseWebhook(array $headers, string $rawBody, array $query = []): WebhookResult
    {
        // Sem gateway configurado: assinatura nunca é válida.
        return new WebhookResult(false);
    }
}
