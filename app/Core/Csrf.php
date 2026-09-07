<?php

namespace App\Core;

/**
 * Proteção CSRF baseada em token de sessão.
 *
 * Gera um token por sessão e valida em todas as requisições que alteram
 * estado (POST/PUT/PATCH/DELETE).
 */
class Csrf
{
    private static function key(): string
    {
        return Config::get('app.security.csrf_token_name', '_csrf');
    }

    public static function token(): string
    {
        $key = self::key();
        $token = Session::get($key);

        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set($key, $token);
        }

        return $token;
    }

    /**
     * Valida o token informado contra o da sessão (comparação constante).
     */
    public static function validate(?string $token): bool
    {
        $stored = Session::get(self::key());
        if (!$stored || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Nome do campo de formulário que carrega o token.
     */
    public static function field(): string
    {
        return Config::get('app.security.csrf_field', '_token');
    }

    /**
     * Retorna o input hidden pronto para uso em formulários.
     */
    public static function input(): string
    {
        $field = htmlspecialchars(self::field(), ENT_QUOTES, 'UTF-8');
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"{$field}\" value=\"{$token}\">";
    }
}
