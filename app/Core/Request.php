<?php

namespace App\Core;

/**
 * Abstração da requisição HTTP.
 *
 * Centraliza acesso a método, URI, entradas (GET/POST), arquivos e headers.
 * Toda leitura de entrada do usuário deve passar por aqui.
 */
class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        // Suporte a method spoofing via campo _method (para PUT/DELETE em forms)
        if ($method === 'POST' && isset($this->post['_method'])) {
            $spoofed = strtoupper($this->post['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofed;
            }
        }
        return $method;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Caminho da URI sem query string, normalizado.
     */
    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $uri = rawurldecode($uri);
        $uri = '/' . trim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    /**
     * Valor de input (POST tem precedência sobre GET).
     */
    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function query(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Todos os dados de entrada combinados.
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * Apenas as chaves informadas.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->get[$key]);
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr($this->server['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    public function isAjax(): bool
    {
        return strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return $this->isAjax() || strpos($accept, 'application/json') !== false;
    }

    /**
     * Token CSRF enviado (via campo de formulário ou header).
     */
    public function csrfToken(): ?string
    {
        $field = Csrf::field();
        return $this->post[$field]
            ?? $this->server['HTTP_X_CSRF_TOKEN']
            ?? null;
    }
}
