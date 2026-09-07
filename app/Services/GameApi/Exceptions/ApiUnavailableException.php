<?php

namespace App\Services\GameApi\Exceptions;

/**
 * A API está inacessível: timeout, erro de conexão, DNS, ou HTTP 5xx.
 *
 * Sinaliza que o site deve recorrer a cache/fallback quando o dado for público,
 * ou informar indisponibilidade temporária quando o dado depender do jogador.
 */
class ApiUnavailableException extends GameApiException
{
}
