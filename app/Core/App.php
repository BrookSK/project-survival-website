<?php

namespace App\Core;

/**
 * Bootstrap da aplicação.
 *
 * Responsável por inicializar configuração, tratamento de erros, sessão,
 * carregar rotas e despachar a requisição. É o ponto orquestrador chamado
 * pelo front controller (public/index.php).
 */
class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function boot(): void
    {
        Config::load();

        date_default_timezone_set(Config::get('app.timezone', 'UTC'));

        $this->configureErrorHandling();

        Response::securityHeaders();

        // Carrega helpers globais
        require_once HELPERS_PATH . '/helpers.php';
    }

    /**
     * Verifica se o sistema está instalado (lock presente e config local válida).
     */
    public function isInstalled(): bool
    {
        return is_file(INSTALL_LOCK_FILE) && is_file(LOCAL_CONFIG_FILE);
    }

    /**
     * Configura exibição/registro de erros conforme o ambiente.
     */
    private function configureErrorHandling(): void
    {
        $display = (bool) Config::get('app.logs.display_errors', false);

        ini_set('display_errors', $display ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->handleException(new \ErrorException(
                    $error['message'], 0, $error['type'], $error['file'], $error['line']
                ));
            }
        });
    }

    /**
     * Tratamento centralizado de exceções: registra e exibe página amigável.
     */
    public function handleException(\Throwable $e): void
    {
        Logger::exception($e);

        if (!headers_sent()) {
            Response::status(500);
        }

        $debug = Config::isDebug();

        try {
            echo View::render('errors.500', [
                'status'    => 500,
                'debug'     => $debug,
                'exception' => $e,
            ]);
        } catch (\Throwable $inner) {
            // Fallback mínimo caso a própria view de erro falhe
            if ($debug) {
                echo '<h1>Erro 500</h1><pre>' . htmlspecialchars((string) $e) . '</pre>';
            } else {
                echo '<h1>Erro interno</h1><p>Ocorreu um erro inesperado. Tente novamente mais tarde.</p>';
            }
        }
        exit;
    }

    /**
     * Carrega os arquivos de definição de rotas.
     */
    public function loadRoutes(): void
    {
        $router = $this->router;
        require ROUTES_PATH . '/web.php';
        require ROUTES_PATH . '/admin.php';
        require ROUTES_PATH . '/api.php';
    }

    public function run(): void
    {
        Session::start();

        // Se não instalado, força o fluxo de instalação (exceto assets).
        if (!$this->isInstalled()) {
            $this->loadInstallRoutes();
        } else {
            $this->applyLocaleSettings();
            $this->shareGlobals();
            $this->loadRoutes();
        }

        $this->router->dispatch(new Request());
    }

    /**
     * Aplica timezone e locale centralizados a partir dos settings administráveis.
     *
     * O boot() define um timezone inicial via Config (UTC por padrão). Uma vez
     * instalado, o valor administrável (settings.timezone) tem precedência, para
     * que datas exibidas sigam a configuração do painel. O locale ativo é fixado
     * no serviço de i18n. Degrada em silêncio caso os settings não existam.
     */
    private function applyLocaleSettings(): void
    {
        try {
            $tz = \App\Services\SettingsService::get('timezone');
            if (is_string($tz) && $tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
                date_default_timezone_set($tz);
            }

            $locale = \App\Services\SettingsService::get('locale');
            if (is_string($locale) && $locale !== '') {
                \App\Services\Lang::setLocale($locale);
            }
        } catch (\Throwable $e) {
            // Antes/depois da instalação a tabela settings pode não estar acessível.
        }
    }

    /**
     * Carrega apenas as rotas do instalador quando o sistema não está instalado.
     */
    private function loadInstallRoutes(): void
    {
        $router = $this->router;
        $installRoutes = ROUTES_PATH . '/install.php';
        if (is_file($installRoutes)) {
            require $installRoutes;
        }
    }

    /**
     * Compartilha dados globais com todas as views (settings, usuário, flashes).
     */
    private function shareGlobals(): void
    {
        // Observação: as flash messages são consumidas diretamente pelos layouts
        // (Session::getFlashes()), portanto NÃO são compartilhadas aqui para evitar
        // consumo duplicado. Compartilhamos apenas dados de leitura repetida.
        View::share('settings', \App\Services\SettingsService::group('general'));
        View::share('social', \App\Services\SettingsService::group('social'));
        View::share('authUser', \App\Services\AuthService::user());
    }

    public function router(): Router
    {
        return $this->router;
    }
}
