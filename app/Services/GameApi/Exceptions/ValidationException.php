<?php

namespace App\Services\GameApi\Exceptions;

/**
 * Erro de validação retornado pela API (HTTP 400).
 *
 * Indica dados de entrada inválidos (ex.: e-mail malformado no registro).
 */
class ValidationException extends GameApiException
{
}
