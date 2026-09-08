<?php

namespace App\Services\Commerce;

/**
 * Erro ao resolver preço/elegibilidade de um produto na Game API.
 * `reason` permite ao chamador diferenciar (not_found, already_owned, ...).
 */
class PricingException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason = 'invalid',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
