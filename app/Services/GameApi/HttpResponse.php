<?php

namespace App\Services\GameApi;

/**
 * Resposta HTTP bruta devolvida pelo HttpClient.
 *
 * Estrutura de transporte, sem interpretação de envelope (isso é papel do
 * GameApiClient). Guarda status, corpo, headers relevantes e latência.
 */
class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
        public readonly int $latencyMs = 0,
        public readonly ?string $transportError = null
    ) {
    }

    /**
     * Houve erro de transporte (timeout, conexão recusada, DNS)?
     */
    public function isTransportError(): bool
    {
        return $this->transportError !== null;
    }

    /**
     * Decodifica o corpo como JSON associativo. Retorna null se inválido.
     */
    public function json(): ?array
    {
        if ($this->body === '') {
            return null;
        }
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function header(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }
}
