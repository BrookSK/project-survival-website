<?php

namespace App\Services\GameApi\Exceptions;

/**
 * Limite de requisições excedido (HTTP 429).
 *
 * O site deve pedir ao usuário que aguarde e nunca tentar contornar o limite.
 * `retryAfter` traz, quando disponível, os segundos sugeridos para nova tentativa.
 */
class RateLimitException extends GameApiException
{
    private ?int $retryAfter;

    public function __construct(
        string $message = 'Muitas requisições. Aguarde um momento e tente novamente.',
        string $apiCode = 'RATE_LIMITED',
        int $httpStatus = 429,
        ?string $requestId = null,
        ?int $retryAfter = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $apiCode, $httpStatus, $requestId, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
