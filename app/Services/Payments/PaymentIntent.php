<?php

namespace App\Services\Payments;

/**
 * Resultado da criação de um pagamento no gateway.
 *
 * DTO neutro (independente de gateway). Valores monetários em centavos.
 * Nunca carrega segredos — apenas o necessário para conduzir o checkout.
 */
class PaymentIntent
{
    /**
     * @param string      $externalId   ID do pagamento no gateway.
     * @param string      $status       Status normalizado (ver PaymentStatus).
     * @param string      $method       pix | credit_card | ...
     * @param int         $amountCents  Valor cobrado, em centavos.
     * @param string      $currency     Moeda (ISO 4217).
     * @param string|null $checkoutUrl  URL de checkout hospedado (cartão), se houver.
     * @param string|null $pixQrCode    Payload PIX copia-e-cola, se houver.
     * @param string|null $pixQrBase64  Imagem do QR (base64), se houver.
     * @param array       $raw          Snapshot sanitizado (SEM segredos).
     */
    public function __construct(
        public readonly string $externalId,
        public readonly string $status,
        public readonly string $method = '',
        public readonly int $amountCents = 0,
        public readonly string $currency = 'BRL',
        public readonly ?string $checkoutUrl = null,
        public readonly ?string $pixQrCode = null,
        public readonly ?string $pixQrBase64 = null,
        public readonly array $raw = []
    ) {
    }
}
