<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;

/**
 * Serviço de autenticação e autorização.
 *
 * Responsável por login/logout, verificação de permissões (RBAC) e
 * proteção contra brute force. O super-admin tem acesso total, tratado aqui.
 */
class AuthService
{
    private const SESSION_KEY = '__auth_user_id';
    private static ?array $cachedUser = null;

    /**
     * Tenta autenticar por e-mail e senha.
     *
     * @return array{success:bool, message?:string, user?:array}
     */
    public static function attempt(string $email, string $password, string $ip): array
    {
        // Bloqueio por brute force
        if (self::isLockedOut($email, $ip)) {
            $minutes = Config::get('app.security.login_lockout_minutes', 15);
            return [
                'success' => false,
                'message' => "Muitas tentativas de login. Tente novamente em {$minutes} minutos.",
            ];
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        $valid = $user
            && (int) $user['is_active'] === 1
            && password_verify($password, $user['password']);

        self::recordAttempt($email, $ip, $valid);

        if (!$valid) {
            return ['success' => false, 'message' => 'Credenciais inválidas.'];
        }

        // Re-hash se o custo/algoritmo mudou
        if (password_needs_rehash($user['password'], Config::get('app.security.password_algo'), [
            'cost' => Config::get('app.security.password_cost'),
        ])) {
            $userModel->updatePassword((int) $user['id'], self::hash($password));
        }

        self::login($user, $ip);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Efetiva o login: fixa a sessão e registra auditoria.
     */
    public static function login(array $user, string $ip): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        self::$cachedUser = null;

        (new User())->updateLastLogin((int) $user['id'], $ip);
        self::clearAttempts($user['email'], $ip);

        AuditService::log('login', 'auth', (string) $user['id'], "Login: {$user['email']}");
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user) {
            AuditService::log('logout', 'auth', (string) $user['id'], "Logout: {$user['email']}");
        }
        Session::remove(self::SESSION_KEY);
        self::$cachedUser = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Retorna o usuário autenticado (com roles e permissões carregadas), ou null.
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser ?: null;
        }

        $id = Session::get(self::SESSION_KEY);
        if (!$id) {
            self::$cachedUser = [];
            return null;
        }

        try {
            $userModel = new User();
            $user = $userModel->find((int) $id);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$user || (int) $user['is_active'] !== 1) {
            Session::remove(self::SESSION_KEY);
            self::$cachedUser = [];
            return null;
        }

        unset($user['password'], $user['remember_token']);
        $user['roles'] = $userModel->roles((int) $id);
        $user['role_slugs'] = array_column($user['roles'], 'slug');
        $user['permissions'] = $userModel->permissionSlugs((int) $id);
        $user['is_super_admin'] = in_array('super-admin', $user['role_slugs'], true);

        self::$cachedUser = $user;
        return $user;
    }

    /**
     * Verifica se o usuário autenticado possui uma permissão.
     * Super-admin sempre retorna true.
     */
    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        if (!empty($user['is_super_admin'])) {
            return true;
        }
        return in_array($permission, $user['permissions'] ?? [], true);
    }

    public static function hasRole(string $slug): bool
    {
        $user = self::user();
        return $user && in_array($slug, $user['role_slugs'] ?? [], true);
    }

    // ---- Hash ----

    public static function hash(string $password): string
    {
        return password_hash($password, Config::get('app.security.password_algo'), [
            'cost' => Config::get('app.security.password_cost'),
        ]);
    }

    // ---- Proteção contra brute force ----

    private static function isLockedOut(string $email, string $ip): bool
    {
        $max = (int) Config::get('app.security.login_max_attempts', 5);
        $minutes = (int) Config::get('app.security.login_lockout_minutes', 15);

        try {
            $count = (int) Database::getInstance()->fetchColumn(
                "SELECT COUNT(*) FROM `login_attempts`
                 WHERE `successful` = 0
                   AND (`email` = :email OR `ip_address` = :ip)
                   AND `attempted_at` > (NOW() - INTERVAL :mins MINUTE)",
                ['email' => $email, 'ip' => $ip, 'mins' => $minutes]
            );
        } catch (\Throwable $e) {
            return false;
        }

        return $count >= $max;
    }

    private static function recordAttempt(string $email, string $ip, bool $successful): void
    {
        try {
            Database::getInstance()->execute(
                "INSERT INTO `login_attempts` (`email`, `ip_address`, `successful`) VALUES (:e, :ip, :s)",
                ['e' => $email, 'ip' => $ip, 's' => $successful ? 1 : 0]
            );
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    private static function clearAttempts(string $email, string $ip): void
    {
        try {
            Database::getInstance()->execute(
                "DELETE FROM `login_attempts` WHERE `email` = :e OR `ip_address` = :ip",
                ['e' => $email, 'ip' => $ip]
            );
        } catch (\Throwable $e) {
            // silencioso
        }
    }
}
