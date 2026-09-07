<?php

namespace App\Services\GameApi\Exceptions;

/**
 * Exceção base da integração com a API do Project Survival.
 *
 * Todas as falhas de comunicação/negócio com a API do jogo derivam desta
 * classe, permitindo que controllers capturem `GameApiException` de forma
 * genérica ou tratem subtipos específicos (auth, rate limit, etc.).
 *
 * Carrega o código legível de máquina (`error.code` do envelope), o status
 * HTTP e um identificador de requisição opcional (correlation id) — nunca
 * expõe tokens, senhas ou stack traces ao usuário final.
 */
class GameApiException extends \RuntimeException
{
    protected string $apiCode;
    protected int $httpStatus;
    protected ?string $requestId;

    public function __construct(
        string $message = 'Falha na comunicação com a API do jogo.',
        string $apiCode = 'GAME_API_ERROR',
        int $httpStatus = 0,
        ?string $requestId = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->apiCode = $apiCode;
        $this->httpStatus = $httpStatus;
        $this->requestId = $requestId;
    }

    public function apiCode(): string
    {
        return $this->apiCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Mensagem segura para exibir ao usuário (sem detalhes técnicos sensíveis).
     */
    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
