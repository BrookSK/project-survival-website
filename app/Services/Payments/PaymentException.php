<?php

namespace App\Services\Payments;

/**
 * Erro na camada de pagamento. `retryable` distingue falhas transitórias
 * (indisponibilidade/rede) de falhas definitivas (validação/regra).
 */
class PaymentException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        public readonly ?int $httpStatus = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function transient(string $message, ?int $status = null): self
    {
        return new self($message, true, $status);
    }

    public static function permanent(string $message, ?int $status = null): self
    {
        return new self($message, false, $status);
    }
}
