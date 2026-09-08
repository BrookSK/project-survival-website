<?php

namespace App\Services\Payments;

/**
 * Resultado da análise de um webhook recebido do gateway.
 *
 * IMPORTANTE: o payload do webhook NUNCA é prova de pagamento. Este DTO só
 * extrai identificadores para que o PaymentService possa consultar o gateway
 * e confirmar o estado REAL. `signatureValid` indica se a assinatura conferiu.
 */
class WebhookResult
{
    public function __construct(
        public readonly bool $signatureValid,
        public readonly ?string $eventId = null,
        public readonly ?string $eventType = null,
        public readonly ?string $externalPaymentId = null,
        public readonly array $raw = []
    ) {
    }

    /**
     * O webhook é acionável (assinatura válida e referencia um pagamento)?
     */
    public function isActionable(): bool
    {
        return $this->signatureValid
            && $this->externalPaymentId !== null
            && $this->externalPaymentId !== '';
    }
}
