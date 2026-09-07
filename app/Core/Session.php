<?php

namespace App\Core;

/**
 * Gerenciamento de sessão seguro.
 *
 * Aplica cookies HttpOnly/Secure/SameSite, regenera o ID periodicamente
 * e fornece flash messages.
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $cfg = Config::get('app.session');

        session_name($cfg['name']);

        session_set_cookie_params([
            'lifetime' => $cfg['lifetime'],
            'path'     => $cfg['path'],
            'domain'   => $cfg['domain'],
            'secure'   => $cfg['secure'],
            'httponly' => $cfg['httponly'],
            'samesite' => $cfg['samesite'],
        ]);

        session_start();
        self::$started = true;

        self::enforceExpiration($cfg);
        self::rotateId($cfg);
    }

    /**
     * Expira a sessão por inatividade além do lifetime configurado.
     */
    private static function enforceExpiration(array $cfg): void
    {
        $now = time();
        $last = $_SESSION['__last_activity'] ?? $now;

        if (($now - $last) > $cfg['lifetime']) {
            self::destroy();
            session_start();
        }

        $_SESSION['__last_activity'] = $now;
    }

    /**
     * Regenera o ID de sessão periodicamente para mitigar fixation.
     */
    private static function rotateId(array $cfg): void
    {
        $now = time();
        $lastRotate = $_SESSION['__last_rotate'] ?? 0;

        if ($lastRotate === 0) {
            $_SESSION['__last_rotate'] = $now;
            return;
        }

        if (($now - $lastRotate) > ($cfg['regenerate_after'] ?? 1800)) {
            session_regenerate_id(true);
            $_SESSION['__last_rotate'] = $now;
        }
    }

    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Regenera o ID (ex.: após login) mantendo os dados.
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['__last_rotate'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }

    // ---- Flash messages ----

    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['__flash'][$type][] = $message;
    }

    /**
     * Recupera e limpa as flash messages.
     */
    public static function getFlashes(): array
    {
        self::start();
        $flashes = $_SESSION['__flash'] ?? [];
        unset($_SESSION['__flash']);
        return $flashes;
    }

    // ---- Old input (repopular formulários) ----

    public static function flashInput(array $input): void
    {
        self::start();
        // Não persiste senhas
        unset($input['password'], $input['password_confirmation'], $input['current_password']);
        $_SESSION['__old_input'] = $input;
    }

    public static function old(string $key, $default = '')
    {
        self::start();
        return $_SESSION['__old_input'][$key] ?? $default;
    }

    public static function clearOld(): void
    {
        self::start();
        unset($_SESSION['__old_input']);
    }
}
