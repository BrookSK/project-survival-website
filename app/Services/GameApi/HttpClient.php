<?php

namespace App\Services\GameApi;

use App\Core\Logger;

/**
 * Cliente HTTP centralizado para a integração com a API do jogo.
 *
 * Único ponto do sistema que executa cURL. Responsável por: método, headers,
 * corpo JSON, timeouts (connect + total), captura de latência e de headers de
 * resposta relevantes, tratamento de erros de transporte e logging seguro
 * (com sanitização de cabeçalhos sensíveis). Nunca lança exceção de negócio —
 * devolve sempre um HttpResponse; a interpretação fica no GameApiClient.
 *
 * Retry: apenas para métodos idempotentes/seguros (GET), com no máximo uma
 * repetição em erro de transporte. POST/PUT/DELETE nunca são repetidos
 * automaticamente para não duplicar operações (purchase/redeem/logout).
 */
class HttpClient
{
    /** Cabeçalhos que jamais devem ser gravados em log. */
    private const SENSITIVE_HEADERS = ['authorization', 'cookie', 'set-cookie', 'x-client-id'];

    /** Cabeçalhos de resposta que capturamos para observabilidade. */
    private const CAPTURED_RESPONSE_HEADERS = ['x-request-id', 'x-correlation-id', 'retry-after', 'content-type'];

    public function __construct(
        private int $timeout = 8,
        private int $connectTimeout = 4
    ) {
        $this->timeout = max(1, $timeout);
        $this->connectTimeout = max(1, min($connectTimeout, $this->timeout));
    }

    /**
     * Executa uma requisição HTTP.
     *
     * @param string $method  GET|POST|PUT|PATCH|DELETE
     * @param string $url      URL absoluta (já validada pelo chamador)
     * @param array  $headers  ['Nome' => 'valor', ...]
     * @param array|null $body Corpo a ser serializado como JSON (ou null)
     */
    public function request(string $method, string $url, array $headers = [], ?array $body = null): HttpResponse
    {
        $method = strtoupper($method);
        $isSafe = $method === 'GET';
        $maxAttempts = $isSafe ? 2 : 1;

        $response = null;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->execute($method, $url, $headers, $body);

            // Só repete GET em erro de transporte (timeout/conexão).
            if (!$response->isTransportError() || !$isSafe || $attempt === $maxAttempts) {
                break;
            }
            usleep(150000); // 150ms de backoff simples antes de uma única nova tentativa
        }

        return $response;
    }

    private function execute(string $method, string $url, array $headers, ?array $body): HttpResponse
    {
        if (!function_exists('curl_init')) {
            // Ambiente sem ext-curl: degrada de forma controlada.
            return new HttpResponse(0, '', [], 0, 'ext-curl indisponível');
        }

        $start = microtime(true);
        $ch = curl_init();

        $requestHeaders = $this->normalizeRequestHeaders($headers);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => $requestHeaders,
        ]);

        if ($body !== null && $method !== 'GET') {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        $raw = curl_exec($ch);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        if ($raw === false) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            $this->logTransportError($method, $url, $errno, $error, $latencyMs);
            return new HttpResponse(0, '', [], $latencyMs, $error !== '' ? $error : 'erro de transporte');
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $bodyContent = substr($raw, $headerSize);
        $responseHeaders = $this->parseResponseHeaders($rawHeaders);

        $this->logRequest($method, $url, $status, $latencyMs, $responseHeaders['x-request-id'] ?? null);

        return new HttpResponse($status, $bodyContent, $responseHeaders, $latencyMs);
    }

    /**
     * Converte ['Nome' => 'valor'] em ['Nome: valor'] garantindo Content-Type/Accept JSON.
     */
    private function normalizeRequestHeaders(array $headers): array
    {
        $defaults = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $merged = array_merge($defaults, $headers);

        $list = [];
        foreach ($merged as $name => $value) {
            $list[] = $name . ': ' . $value;
        }
        return $list;
    }

    /**
     * Extrai apenas os headers de resposta relevantes (chaves em minúsculas).
     */
    private function parseResponseHeaders(string $rawHeaders): array
    {
        $result = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            $pos = strpos($line, ':');
            if ($pos === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $pos)));
            $value = trim(substr($line, $pos + 1));
            if (in_array($name, self::CAPTURED_RESPONSE_HEADERS, true)) {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * Log de requisição bem-sucedida (sem corpo, sem headers sensíveis).
     */
    private function logRequest(string $method, string $url, int $status, int $latencyMs, ?string $requestId): void
    {
        $context = [
            'method'     => $method,
            'url'        => $this->safeUrl($url),
            'status'     => $status,
            'latency_ms' => $latencyMs,
        ];
        if ($requestId) {
            $context['request_id'] = $requestId;
        }
        Logger::info('game_api.request', $context);
    }

    private function logTransportError(string $method, string $url, int $errno, string $error, int $latencyMs): void
    {
        Logger::warning('game_api.transport_error', [
            'method'     => $method,
            'url'        => $this->safeUrl($url),
            'errno'      => $errno,
            'error'      => $error,
            'latency_ms' => $latencyMs,
        ]);
    }

    /**
     * Remove query string e credenciais embutidas da URL antes de logar.
     */
    private function safeUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '[url inválida]';
        }
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        return $scheme . '://' . $host . $port . $path;
    }
}
