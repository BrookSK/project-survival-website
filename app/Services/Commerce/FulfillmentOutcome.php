<?php

namespace App\Services\Commerce;

/**
 * Resultado de uma tentativa de concessão junto à Game API.
 *
 * `state`: fulfilled | processing | pending | failed
 *   - fulfilled  = Game API confirmou a concessão.
 *   - processing = aceito, concessão assíncrona (reconsultar depois).
 *   - pending    = não foi possível agora (API off/transitório) -> retry.
 *   - failed     = falha DEFINITIVA (não fazer retry).
 *
 * Este DTO NUNCA contém o entitlement em si — a autoridade é a Game API.
 */
class FulfillmentOutcome
{
    public function __construct(
        public readonly string $state,
        public readonly ?string $externalId = null,
        public readonly ?string $error = null,
        public readonly bool $idempotentReplay = false
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->state === 'fulfilled';
    }

    public function shouldRetry(): bool
    {
        return in_array($this->state, ['pending', 'processing'], true);
    }

    public function isPermanentFailure(): bool
    {
        return $this->state === 'failed';
    }
}
