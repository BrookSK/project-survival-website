<?php

namespace App\Services\GameApi\Exceptions;

/**
 * Falha de autenticação/autorização (HTTP 401/403).
 *
 * 401: token ausente/expirado/inválido — o fluxo de refresh deve ser tentado
 * uma vez; se falhar, encerra-se a sessão do jogador.
 * 403: autenticado mas sem permissão para o recurso.
 */
class AuthenticationException extends GameApiException
{
}
