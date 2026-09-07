<?php

namespace App\Services\GameApi;

/**
 * Dados privados do jogador: entitlements, inventário e resgate de código.
 *
 * TODAS as chamadas exigem o token do jogador e NADA aqui é cacheado de forma
 * compartilhada — são dados por jogador (fonte de verdade: a API). Os erros
 * (401/404/409/429) sobem como exceções para o controller tratar e exibir a
 * mensagem adequada.
 */
class GamePlayerService
{
    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * Entitlements do jogador — o que ele realmente possui (fonte de verdade).
     *
     * @return string[]
     */
    public function entitlements(string $token): array
    {
        $data = $this->client->get('/player/entitlements', [], $token);
        $list = $data['entitlements'] ?? [];
        return is_array($list) ? array_values($list) : [];
    }

    /**
     * Inventário online: itens possuídos e pedidos.
     *
     * @return array{owned:array, orders:array}
     */
    public function inventory(string $token): array
    {
        $data = $this->client->get('/player/inventory', [], $token);
        return [
            'owned'  => is_array($data['owned'] ?? null) ? $data['owned'] : [],
            'orders' => is_array($data['orders'] ?? null) ? $data['orders'] : [],
        ];
    }

    /**
     * Resgata um código. A concessão é decidida pelo servidor.
     * Pode lançar NotFound (404), Conflict (409) ou RateLimit (429).
     *
     * @return array{redeemed:bool, type:?string, value:?string}
     */
    public function redeem(string $token, string $code): array
    {
        $data = $this->client->post('/redeem', ['code' => $code], $token);
        return [
            'redeemed' => !empty($data['redeemed']),
            'type'     => $data['type'] ?? null,
            'value'    => $data['value'] ?? null,
        ];
    }
}
