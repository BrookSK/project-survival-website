<?php

use App\Services\GameApi\HttpClient;
use App\Services\GameApi\HttpResponse;

/**
 * HttpClient de teste: devolve respostas programadas sem tocar na rede.
 *
 * Estende o HttpClient real e sobrescreve request(). É injetado no
 * GameApiClient (5º parâmetro do construtor), permitindo testar todo o
 * fluxo de interpretação de envelope/status sem servidor. NUNCA usado em
 * produção — vive apenas em tests/.
 */
class MockHttpClient extends HttpClient
{
    /** @var HttpResponse[] fila de respostas a devolver, em ordem */
    private array $queue = [];

    /** @var array<int,array{method:string,url:string,headers:array,body:?array}> */
    public array $calls = [];

    public function __construct()
    {
        parent::__construct(5, 2);
    }

    /**
     * Enfileira uma resposta JSON com status e headers opcionais.
     */
    public function pushJson(int $status, array $payload, array $headers = []): self
    {
        $this->queue[] = new HttpResponse(
            $status,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $headers
        );
        return $this;
    }

    /**
     * Enfileira uma resposta com corpo bruto (para simular JSON inválido).
     */
    public function pushRaw(int $status, string $body, array $headers = []): self
    {
        $this->queue[] = new HttpResponse($status, $body, $headers);
        return $this;
    }

    /**
     * Enfileira um erro de transporte (timeout/conexão).
     */
    public function pushTransportError(string $message = 'timeout'): self
    {
        $this->queue[] = new HttpResponse(0, '', [], 0, $message);
        return $this;
    }

    public function request(string $method, string $url, array $headers = [], ?array $body = null): HttpResponse
    {
        $this->calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

        if ($this->queue === []) {
            // Sem resposta programada: simula indisponibilidade.
            return new HttpResponse(0, '', [], 0, 'no mock response queued');
        }
        return array_shift($this->queue);
    }

    /** Último cabeçalho Authorization enviado (para verificar Bearer). */
    public function lastAuthHeader(): ?string
    {
        $last = end($this->calls) ?: [];
        return $last['headers']['Authorization'] ?? null;
    }
}
