<?php

namespace App\Services\GameApi;

use App\Services\GameApi\Exceptions\ApiUnavailableException;
use App\Services\GameApi\Exceptions\AuthenticationException;
use App\Services\GameApi\Exceptions\ConflictException;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\Exceptions\NotFoundException;
use App\Services\GameApi\Exceptions\RateLimitException;
use App\Services\GameApi\Exceptions\ValidationException;

/**
 * Traduz exceções da integração em mensagens amigáveis ao usuário final.
 *
 * Preserva o significado técnico (para o usuário entender o que houve) sem
 * expor detalhes sensíveis, stack traces ou códigos internos. Usa a mensagem
 * da API quando ela é apresentável; caso contrário, aplica um texto padrão por
 * tipo de erro.
 */
class ErrorPresenter
{
    public static function message(GameApiException $e, ?string $context = null): string
    {
        $apiMessage = trim($e->getMessage());

        if ($e instanceof ValidationException) {
            return $apiMessage !== '' ? $apiMessage : 'Verifique os dados informados e tente novamente.';
        }
        if ($e instanceof AuthenticationException) {
            if ($e->httpStatus() === 403) {
                return 'Você não tem permissão para esta ação.';
            }
            return $apiMessage !== '' ? $apiMessage : 'Sua sessão expirou. Entre novamente.';
        }
        if ($e instanceof NotFoundException) {
            return $apiMessage !== '' ? $apiMessage : 'Recurso não encontrado.';
        }
        if ($e instanceof ConflictException) {
            return $apiMessage !== '' ? $apiMessage : 'Não foi possível concluir devido a um conflito.';
        }
        if ($e instanceof RateLimitException) {
            $secs = $e->retryAfter();
            $suffix = $secs ? " Aguarde {$secs}s." : ' Aguarde um momento.';
            return 'Muitas tentativas.' . $suffix;
        }
        if ($e instanceof ApiUnavailableException) {
            return $context
                ? "{$context} está temporariamente indisponível. Tente novamente em instantes."
                : 'Serviço temporariamente indisponível. Tente novamente em instantes.';
        }

        // GameApiException genérica / resposta inválida.
        return 'Não foi possível concluir a operação. Tente novamente.';
    }
}
