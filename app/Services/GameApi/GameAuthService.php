<?php

namespace App\Services\GameApi;

/**
 * Autenticação do jogador contra a API do jogo.
 *
 * Encapsula os endpoints /auth/* e /me. Este serviço NÃO persiste tokens nem
 * mexe em sessão — apenas conversa com a API e devolve os dados. A guarda dos
 * tokens em sessão segura é responsabilidade da camada PlayerSession/controller.
 *
 * A senha do jogador nunca é armazenada nem logada; trafega apenas para o
 * endpoint oficial correspondente.
 */
class GameAuthService
{
    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * Cria uma conta de jogador. Pode lançar Validation (400) ou Conflict (409).
     */
    public function register(string $email, string $username, string $password): array
    {
        $data = $this->client->post('/auth/register', [
            'email'    => $email,
            'username' => $username,
            'password' => $password,
        ]);
        return is_array($data) ? $data : [];
    }

    /**
     * Autentica por identifier (email ou username) + senha.
     *
     * @return array{access_token:string, refresh_token:string, user:array}
     */
    public function login(string $identifier, string $password): array
    {
        $data = $this->client->post('/auth/login', [
            'identifier' => $identifier,
            'password'   => $password,
        ]);

        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'user'          => is_array($data['user'] ?? null) ? $data['user'] : [],
        ];
    }

    /**
     * Rotaciona os tokens usando o refresh token. Pode lançar Authentication (401).
     *
     * @return array{access_token:string, refresh_token:string, user:array}
     */
    public function refresh(string $userId, string $refreshToken): array
    {
        $data = $this->client->post('/auth/refresh', [
            'user_id'       => $userId,
            'refresh_token' => $refreshToken,
        ]);

        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'user'          => is_array($data['user'] ?? null) ? $data['user'] : [],
        ];
    }

    /**
     * Revoga um refresh token na API (autenticado).
     */
    public function logout(string $token, string $refreshToken): void
    {
        try {
            $this->client->post('/auth/logout', ['refresh_token' => $refreshToken], $token);
        } catch (\Throwable $e) {
            // Logout na API é best-effort: a sessão local será destruída de todo modo.
        }
    }

    /**
     * Altera a senha do jogador autenticado.
     */
    public function changePassword(string $token, string $currentPassword, string $newPassword): void
    {
        $this->client->post('/auth/change-password', [
            'current_password' => $currentPassword,
            'new_password'     => $newPassword,
        ], $token);
    }

    /**
     * Dados do jogador autenticado (GET /me).
     */
    public function me(string $token): array
    {
        $data = $this->client->get('/me', [], $token);
        return is_array($data) ? $data : [];
    }
}
