<?php

namespace App\Services\GameApi;

use App\Core\Logger;
use App\Core\Session;
use App\Services\GameApi\Exceptions\AuthenticationException;
use App\Services\GameApi\Exceptions\GameApiException;

/**
 * Sessão do jogador autenticado na API do jogo.
 *
 * Os tokens (access/refresh) e os dados do jogador vivem EXCLUSIVAMENTE na
 * sessão server-side do PHP (cookie HttpOnly/SameSite já configurado em
 * App\Core\Session). Nunca são enviados ao navegador, impressos em HTML,
 * expostos ao JavaScript ou gravados em log.
 *
 * É independente do AuthService (que autentica administradores do site): um
 * jogador logado aqui NÃO é administrador do site, e vice-versa.
 *
 * O método `withAuth()` centraliza o fluxo de refresh: executa uma operação
 * autenticada; se receber 401, tenta renovar os tokens UMA vez e repete; se o
 * refresh falhar, encerra a sessão do jogador (sem loop infinito).
 */
class PlayerSession
{
    private const KEY = '__player';

    /**
     * Persiste a sessão do jogador após login/refresh bem-sucedido.
     *
     * @param array $tokens ['access_token'=>..., 'refresh_token'=>..., 'user'=>[...]]
     */
    public static function store(array $tokens): void
    {
        $user = is_array($tokens['user'] ?? null) ? $tokens['user'] : [];
        $userId = (string) ($user['id'] ?? $tokens['user_id'] ?? '');

        Session::regenerate(); // mitiga fixation ao elevar privilégio
        Session::set(self::KEY, [
            'access_token'  => (string) ($tokens['access_token'] ?? ''),
            'refresh_token' => (string) ($tokens['refresh_token'] ?? ''),
            'user_id'       => $userId,
            'user'          => $user,
            'since'         => time(),
        ]);
    }

    /**
     * Atualiza apenas os tokens (após um refresh), preservando dados do jogador.
     */
    public static function updateTokens(string $accessToken, string $refreshToken, array $user = []): void
    {
        $current = Session::get(self::KEY, []);
        if (!is_array($current)) {
            $current = [];
        }
        $current['access_token']  = $accessToken;
        $current['refresh_token'] = $refreshToken;
        if ($user !== []) {
            $current['user'] = $user;
            $current['user_id'] = (string) ($user['id'] ?? ($current['user_id'] ?? ''));
        }
        Session::set(self::KEY, $current);
    }

    public static function check(): bool
    {
        $data = Session::get(self::KEY);
        return is_array($data) && !empty($data['access_token']);
    }

    public static function accessToken(): ?string
    {
        $data = Session::get(self::KEY, []);
        return is_array($data) && !empty($data['access_token']) ? $data['access_token'] : null;
    }

    public static function refreshToken(): ?string
    {
        $data = Session::get(self::KEY, []);
        return is_array($data) && !empty($data['refresh_token']) ? $data['refresh_token'] : null;
    }

    public static function userId(): ?string
    {
        $data = Session::get(self::KEY, []);
        return is_array($data) && !empty($data['user_id']) ? (string) $data['user_id'] : null;
    }

    /**
     * Dados públicos do jogador (nunca inclui tokens).
     */
    public static function user(): array
    {
        $data = Session::get(self::KEY, []);
        return is_array($data) && is_array($data['user'] ?? null) ? $data['user'] : [];
    }

    /**
     * Nome de exibição amigável (username / nome / e-mail).
     */
    public static function displayName(): string
    {
        $u = self::user();
        return (string) ($u['username'] ?? $u['name'] ?? $u['email'] ?? 'Jogador');
    }

    /**
     * Atualiza os dados do jogador em sessão (ex.: após GET /me).
     */
    public static function setUser(array $user): void
    {
        $current = Session::get(self::KEY, []);
        if (!is_array($current)) {
            return;
        }
        $current['user'] = $user;
        if (!empty($user['id'])) {
            $current['user_id'] = (string) $user['id'];
        }
        Session::set(self::KEY, $current);
    }

    /**
     * Encerra a sessão do jogador. Best-effort de revogar o refresh na API.
     */
    public static function logout(): void
    {
        $token = self::accessToken();
        $refresh = self::refreshToken();

        if ($token && $refresh) {
            (new GameAuthService())->logout($token, $refresh);
        }

        self::forget();
    }

    /**
     * Remove apenas os dados do jogador da sessão (sem tocar em outros dados).
     */
    public static function forget(): void
    {
        Session::remove(self::KEY);
    }

    /**
     * Executa uma operação autenticada com refresh automático em caso de 401.
     *
     * @template T
     * @param callable(string):T $operation  recebe o access token e retorna o resultado
     * @return T
     *
     * @throws AuthenticationException se não houver sessão ou o refresh falhar
     * @throws GameApiException        para outros erros da API
     */
    public static function withAuth(callable $operation)
    {
        $token = self::accessToken();
        if ($token === null) {
            throw new AuthenticationException('Sessão do jogador ausente.', 'NO_PLAYER_SESSION', 401);
        }

        try {
            return $operation($token);
        } catch (AuthenticationException $e) {
            // Apenas 401 dispara refresh; 403 (sem permissão) sobe direto.
            if ($e->httpStatus() === 403) {
                throw $e;
            }

            $newToken = self::attemptRefresh();
            if ($newToken === null) {
                // Refresh falhou: encerra sessão local e propaga 401 (sem loop).
                self::forget();
                throw new AuthenticationException('Sua sessão expirou. Entre novamente.', 'SESSION_EXPIRED', 401);
            }

            // Uma única nova tentativa com o token renovado.
            return $operation($newToken);
        }
    }

    /**
     * Tenta renovar os tokens uma única vez. Retorna o novo access token ou null.
     */
    private static function attemptRefresh(): ?string
    {
        $userId = self::userId();
        $refresh = self::refreshToken();
        if (!$userId || !$refresh) {
            return null;
        }

        try {
            $tokens = (new GameAuthService())->refresh($userId, $refresh);
        } catch (\Throwable $e) {
            Logger::warning('game_api.refresh_failed');
            return null;
        }

        if (empty($tokens['access_token'])) {
            return null;
        }

        self::updateTokens(
            $tokens['access_token'],
            $tokens['refresh_token'] ?: $refresh,
            $tokens['user'] ?? []
        );

        return $tokens['access_token'];
    }
}
