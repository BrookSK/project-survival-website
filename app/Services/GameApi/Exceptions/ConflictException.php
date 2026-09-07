<?php

namespace App\Services\GameApi\Exceptions;

/**
 * Conflito de estado (HTTP 409).
 *
 * Ex.: código de resgate expirado/esgotado/já utilizado, e-mail já cadastrado.
 */
class ConflictException extends GameApiException
{
}
