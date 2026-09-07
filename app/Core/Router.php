<?php

namespace App\Core;

/**
 * Roteador com suporte a:
 * - Métodos HTTP (GET, POST, PUT, PATCH, DELETE)
 * - Parâmetros nomeados na URL (ex.: /noticias/{slug})
 * - Grupos com prefixo e middlewares
 *
 * As rotas são definidas nos arquivos routes/*.php.
 */
class Router
{
    /** @var array<int,array> */
    private array $routes = [];

    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * Agrupa rotas sob um prefixo e/ou middlewares comuns.
     */
    public function group(array $options, callable $callback): void
    {
        $prevPrefix = $this->groupPrefix;
        $prevMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $prevPrefix . ($options['prefix'] ?? '');
        $this->groupMiddleware = array_merge($prevMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $prevPrefix;
        $this->groupMiddleware = $prevMiddleware;
    }

    private function add(string $method, string $path, $handler, array $middleware): void
    {
        $path = $this->groupPrefix . $path;
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            // mantém raiz
        }

        $this->routes[] = [
            'method'     => $method,
            'path'       => $path === '' ? '/' : $path,
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'regex'      => $this->compile($path === '' ? '/' : $path),
        ];
    }

    /**
     * Compila um path com {param} em uma regex nomeada.
     */
    private function compile(string $path): string
    {
        // Suporta {param} (um segmento) e {param:*} (captura tudo, incluindo barras)
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(:\*)?\}/', function ($m) {
            $pattern = isset($m[2]) && $m[2] === ':*' ? '.+' : '[^/]+';
            return '(?P<' . $m[1] . '>' . $pattern . ')';
        }, $path);

        return '#^' . $regex . '$#';
    }

    /**
     * Resolve a requisição atual, executa middlewares e o handler.
     */
    public function dispatch(Request $request): void
    {
        $uri = $request->uri();
        $method = $request->method();

        $matchedByPath = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }
            $matchedByPath = true;

            if ($route['method'] !== $method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Executa middlewares; qualquer um pode interromper o fluxo.
            foreach ($route['middleware'] as $middleware) {
                $instance = is_string($middleware) ? new $middleware() : $middleware;
                $result = $instance->handle($request);
                if ($result === false) {
                    return; // middleware já tratou a resposta (redirect/erro)
                }
            }

            $this->invoke($route['handler'], $request, $params);
            return;
        }

        // 405 se o caminho existe mas o método não bate; senão 404.
        if ($matchedByPath) {
            $this->abort(405, $request);
        } else {
            $this->abort(404, $request);
        }
    }

    /**
     * Invoca o handler: 'Controller@method' ou callable.
     */
    private function invoke($handler, Request $request, array $params): void
    {
        if (is_callable($handler)) {
            $handler($request, $params);
            return;
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler, 2);
            $fqcn = 'App\\Controllers\\' . $class;

            if (!class_exists($fqcn)) {
                throw new \RuntimeException("Controller não encontrado: {$fqcn}");
            }

            $controller = new $fqcn();
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Ação não encontrada: {$fqcn}@{$method}");
            }

            $controller->$method($request, $params);
            return;
        }

        throw new \RuntimeException('Handler de rota inválido.');
    }

    public function abort(int $code, Request $request): void
    {
        Response::status($code);

        if ($request->wantsJson()) {
            Response::json(['error' => true, 'status' => $code], $code);
            return;
        }

        $view = 'errors.' . $code;
        $file = VIEWS_PATH . '/errors/' . $code . '.php';
        if (is_file($file)) {
            echo View::render($view, ['status' => $code]);
        } else {
            echo View::render('errors.generic', ['status' => $code]);
        }
    }
}
