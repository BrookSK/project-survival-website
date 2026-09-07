<?php

namespace App\Services\GameApi;

use App\Core\Logger;
use App\Services\GameApi\Exceptions\ApiUnavailableException;
use App\Services\GameApi\Exceptions\AuthenticationException;
use App\Services\GameApi\Exceptions\ConflictException;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\Exceptions\NotFoundException;
use App\Services\GameApi\Exceptions\RateLimitException;
use App\Services\GameApi\Exceptions\ValidationException;

/**
 * Cliente da API oficial do Project Survival (/api/v1).
 *
 * Camada única que conhece o contrato de transporte da API: monta a URL a
 * partir da base configurada, injeta os cabeçalhos (Client-Id público e, quando
 * fornecido, o Bearer do jogador), delega o HTTP ao HttpClient e interpreta o
 * envelope padrão `{ success, data }` / `{ success, error:{code,message} }`.
 *
 * O mapeamento de status HTTP para exceções acontece exclusivamente aqui, para
 * não duplicar essa lógica nos Services. Métodos de leitura pública podem pedir
 * o HttpResponse cru (para permitir fallback de cache no chamador).
 *
 * Não implementa cache, sessão nem regras de negócio — isso é responsabilidade
 * dos Services que o consomem.
 */
class GameApiClient
{
    private string $baseUrl;
    private string $clientId;
    private bool $enabled;
    private HttpClient $http;

    /**
     * @param string $baseUrl  URL base já validada (ex.: http://localhost:4000/api/v1)
     * @param string $clientId client_id público (não é segredo)
     * @param int    $timeout  timeout total em segundos
     * @param bool   $enabled  integração ativa?
     */
    public function __construct(string $baseUrl, string $clientId = '', int $timeout = 8, bool $enabled = true, ?HttpClient $http = null)
    {
        $this->baseUrl = UrlGuard::normalizeBaseUrl($baseUrl);
        $this->clientId = $clientId;
        $this->enabled = $enabled;
        $this->http = $http ?? new HttpClient($timeout);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->baseUrl !== '';
    }

    /**
     * GET que devolve o `data` do envelope (ou lança exceção).
     *
     * @param array $query        parâmetros de query string
     * @param string|null $token  Bearer do jogador (endpoints autenticados)
     */
    public function get(string $path, array $query = [], ?string $token = null)
    {
        return $this->send('GET', $path, null, $token, $query);
    }

    /**
     * POST que devolve o `data` do envelope (ou lança exceção).
     */
    public function post(string $path, array $body = [], ?string $token = null)
    {
        return $this->send('POST', $path, $body, $token);
    }

    public function put(string $path, array $body = [], ?string $token = null)
    {
        return $this->send('PUT', $path, $body, $token);
    }

    /**
     * Executa a requisição bruta e devolve o HttpResponse (sem interpretar
     * envelope). Útil para health check e para o chamador decidir fallback.
     */
    public function raw(string $method, string $path, array $query = [], ?array $body = null, ?string $token = null): HttpResponse
    {
        $this->assertEnabled();
        $url = $this->buildUrl($path, $query);
        return $this->http->request($method, $url, $this->buildHeaders($token), $body);
    }

    /**
     * Núcleo: envia, interpreta envelope e mapeia status → exceção/dado.
     */
    private function send(string $method, string $path, ?array $body, ?string $token, array $query = [])
    {
        $this->assertEnabled();

        $url = $this->buildUrl($path, $query);
        $response = $this->http->request($method, $url, $this->buildHeaders($token), $body);

        return $this->interpret($response);
    }

    /**
     * Interpreta um HttpResponse conforme o contrato e devolve `data` ou lança.
     */
    public function interpret(HttpResponse $response)
    {
        $requestId = $response->header('x-request-id') ?? $response->header('x-correlation-id');

        // Erro de transporte (timeout/conexão) → API indisponível.
        if ($response->isTransportError() || $response->status === 0) {
            throw new ApiUnavailableException(
                'A API do jogo está indisponível no momento.',
                'API_UNAVAILABLE',
                0,
                $requestId
            );
        }

        $payload = $response->json();
        $status = $response->status;

        // 5xx → indisponível (com fallback possível para leituras públicas).
        if ($status >= 500) {
            throw new ApiUnavailableException(
                $this->messageFrom($payload, 'A API do jogo retornou um erro temporário.'),
                $this->codeFrom($payload, 'API_SERVER_ERROR'),
                $status,
                $requestId
            );
        }

        // Corpo não-JSON em resposta que deveria ser JSON.
        if ($payload === null) {
            throw new GameApiException(
                'Resposta inválida da API do jogo.',
                'INVALID_RESPONSE',
                $status,
                $requestId
            );
        }

        $success = $payload['success'] ?? null;

        // Sucesso (2xx com success:true).
        if ($status >= 200 && $status < 300 && $success === true) {
            return $payload['data'] ?? null;
        }

        // A partir daqui é erro: extrai code/message do envelope.
        $code = $this->codeFrom($payload, 'GAME_API_ERROR');
        $message = $this->messageFrom($payload, 'Não foi possível concluir a operação.');

        switch ($status) {
            case 400:
                throw new ValidationException($message, $code, $status, $requestId);
            case 401:
            case 403:
                throw new AuthenticationException($message, $code, $status, $requestId);
            case 404:
                throw new NotFoundException($message, $code, $status, $requestId);
            case 409:
                throw new ConflictException($message, $code, $status, $requestId);
            case 429:
                $retryAfter = $response->header('retry-after');
                throw new RateLimitException(
                    $message !== '' ? $message : 'Muitas tentativas. Aguarde e tente novamente.',
                    $code,
                    $status,
                    $requestId,
                    is_numeric($retryAfter) ? (int) $retryAfter : null
                );
            default:
                throw new GameApiException($message, $code, $status, $requestId);
        }
    }

    private function assertEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new ApiUnavailableException(
                'A integração com a API do jogo está desativada.',
                'INTEGRATION_DISABLED',
                0
            );
        }
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    /**
     * Monta os cabeçalhos. O client_id é público; o token é do jogador.
     */
    private function buildHeaders(?string $token): array
    {
        $headers = [];
        if ($this->clientId !== '') {
            $headers['X-Client-Id'] = $this->clientId;
        }
        if ($token !== null && $token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }

    private function codeFrom(?array $payload, string $default): string
    {
        return (string) ($payload['error']['code'] ?? $default);
    }

    private function messageFrom(?array $payload, string $default): string
    {
        $msg = $payload['error']['message'] ?? null;
        return is_string($msg) && $msg !== '' ? $msg : $default;
    }
}
