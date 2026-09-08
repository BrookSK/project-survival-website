<?php

namespace App\Services\Payments;

/**
 * Resultado de um estorno junto ao gateway. Valores em centavos.
 */
class RefundResult
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $status,
        public readonly int $refundedCents = 0,
        public readonly array $raw = []
    ) {
    }
}
