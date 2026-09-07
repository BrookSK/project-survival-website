<?php

namespace App\Services\GameApi;

/**
 * Consulta o health check da API do jogo (GET /health).
 *
 * Retorna uma estrutura normalizada com o estado da API e do banco, versão,
 * latência e horário — usada no painel administrativo (diagnóstico) e no
 * componente público "Status do jogo". Nunca lança: em falha, devolve um
 * resultado com `online=false` para que a UI decida como apresentar.
 */
class GameHealthService
{
    private GameApiClient $client;

    public function __construct(?GameApiClient $client = null)
    {
        $this->client = $client ?? GameApiConfig::client();
    }

    /**
     * @return array{
     *   online:bool, database:?string, api_version:?string, time:?string,
     *   latency_ms:int, http_status:int, error:?string
     * }
     */
    public function check(): array
    {
        $result = [
            'online'      => false,
            'database'    => null,
            'api_version' => null,
            'time'        => null,
            'latency_ms'  => 0,
            'http_status' => 0,
            'error'       => null,
        ];

        if (!GameApiConfig::isEnabled()) {
            $result['error'] = 'Integração desativada.';
            return $result;
        }

        try {
            $response = $this->client->raw('GET', '/health');
        } catch (\Throwable $e) {
            $result['error'] = 'Falha ao contatar a API.';
            return $result;
        }

        $result['latency_ms'] = $response->latencyMs;
        $result['http_status'] = $response->status;

        if ($response->isTransportError()) {
            $result['error'] = 'API inacessível (timeout ou conexão recusada).';
            return $result;
        }

        $payload = $response->json();
        $data = is_array($payload) ? ($payload['data'] ?? $payload) : [];

        $result['online']      = $response->status >= 200 && $response->status < 300 && ($payload['success'] ?? true) !== false;
        $result['database']    = $data['database'] ?? null;
        $result['api_version'] = $data['api_version'] ?? null;
        $result['time']        = $data['time'] ?? null;

        if (!$result['online']) {
            $result['error'] = 'A API respondeu com status ' . $response->status . '.';
        }

        return $result;
    }
}
