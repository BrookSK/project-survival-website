<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\MediaService;

/**
 * Administração do sistema: informações, migrations e diagnóstico (health check).
 */
class SystemController extends Controller
{
    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'json'];
    private const RECOMMENDED_EXTENSIONS = ['gd', 'curl', 'zip'];

    public function index(Request $request): void
    {
        $this->authorize('system.view');

        $migrator = new Migrator();
        try {
            $pending = $migrator->pending();
            $executed = $migrator->executed();
        } catch (\Throwable $e) {
            $pending = [];
            $executed = [];
        }

        $this->viewAdmin('admin.system.index', [
            'title'       => 'Sistema',
            'breadcrumbs' => [['label' => 'Sistema']],
            'info'        => $this->systemInfo(),
            'pending'     => $pending,
            'executedCount' => count($executed),
        ]);
    }

    /**
     * Executa migrations pendentes (ação administrativa explícita).
     */
    public function migrate(Request $request): void
    {
        $this->authorize('system.manage');
        $this->verifyCsrf($request);

        try {
            $migrator = new Migrator();
            $ran = $migrator->migrate();
            $migrator->seed();
            // Dados estruturais/settings podem ter mudado: invalida caches derivados.
            \App\Services\SettingsService::flush();
            \App\Services\CacheService::flush();
            AuditService::log('migrate', 'system', null, 'Executou migrations: ' . implode(', ', $ran));
            Session::flash('success', $ran ? (count($ran) . ' migration(s) executada(s).') : 'Nenhuma migration pendente.');
        } catch (\Throwable $e) {
            \App\Core\Logger::exception($e);
            Session::flash('error', 'Falha ao executar migrations: ' . $e->getMessage());
        }
        $this->redirect('/admin/sistema');
    }

    /**
     * Limpa o cache de dados da aplicação (settings, menus, sitemap).
     */
    public function clearCache(Request $request): void
    {
        $this->authorize('system.manage');
        $this->verifyCsrf($request);

        $removed = \App\Services\CacheService::flush();
        \App\Services\SettingsService::flush();
        AuditService::log('cache', 'system', null, "Limpou o cache ({$removed} arquivo(s)).");
        Session::flash('success', "Cache limpo ({$removed} arquivo(s) removido(s)).");
        $this->redirect('/admin/sistema');
    }

    /**
     * Página de diagnóstico (health check).
     */
    public function diagnostics(Request $request): void
    {
        $this->authorize('system.view');

        $this->viewAdmin('admin.system.diagnostics', [
            'title'       => 'Diagnóstico',
            'breadcrumbs' => [['label' => 'Sistema', 'url' => '/admin/sistema'], ['label' => 'Diagnóstico']],
            'checks'      => $this->runChecks(),
        ]);
    }

    private function systemInfo(): array
    {
        $db = Database::getInstance();
        $dbVersion = '—';
        try {
            $dbVersion = (string) $db->fetchColumn('SELECT VERSION()');
        } catch (\Throwable $e) {
        }

        return [
            'app_version' => APP_VERSION,
            'php_version' => PHP_VERSION,
            'db_version'  => $dbVersion,
            'environment' => Config::get('app.environment'),
            'timezone'    => date_default_timezone_get(),
            'gd'          => MediaService::gdAvailable() ? 'Disponível' : 'Indisponível',
            'webp'        => MediaService::webpSupported() ? 'Suportado' : 'Não suportado',
        ];
    }

    /**
     * Verificações de saúde retornando status OK/warning/error.
     */
    private function runChecks(): array
    {
        $checks = [];

        // PHP
        $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
        $checks[] = ['label' => 'PHP >= 8.0', 'status' => $phpOk ? 'ok' : 'error', 'detail' => PHP_VERSION];

        // Extensões obrigatórias
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = ['label' => "Extensão {$ext}", 'status' => $loaded ? 'ok' : 'error', 'detail' => $loaded ? 'Carregada' : 'Ausente (obrigatória)'];
        }
        // Recomendadas
        foreach (self::RECOMMENDED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = ['label' => "Extensão {$ext}", 'status' => $loaded ? 'ok' : 'warning', 'detail' => $loaded ? 'Carregada' : 'Ausente (recomendada)'];
        }

        // Permissões de escrita
        foreach ([
            'storage/' => STORAGE_PATH,
            'storage/logs' => LOGS_PATH,
            'storage/cache' => CACHE_PATH,
            'public/uploads' => PUBLIC_UPLOADS_PATH,
            'config/' => CONFIG_PATH,
        ] as $label => $path) {
            $writable = is_writable($path);
            $checks[] = ['label' => "Gravável: {$label}", 'status' => $writable ? 'ok' : 'warning', 'detail' => $writable ? 'OK' : 'Sem permissão'];
        }

        // Banco de dados
        try {
            Database::getInstance()->fetchColumn('SELECT 1');
            $checks[] = ['label' => 'Conexão com o banco', 'status' => 'ok', 'detail' => 'Conectado'];
        } catch (\Throwable $e) {
            $checks[] = ['label' => 'Conexão com o banco', 'status' => 'error', 'detail' => 'Falha'];
        }

        // Migrations pendentes
        try {
            $pending = (new Migrator())->pending();
            $checks[] = [
                'label'  => 'Migrations',
                'status' => empty($pending) ? 'ok' : 'warning',
                'detail' => empty($pending) ? 'Atualizadas' : count($pending) . ' pendente(s)',
            ];
        } catch (\Throwable $e) {
            $checks[] = ['label' => 'Migrations', 'status' => 'error', 'detail' => 'Erro ao verificar'];
        }

        // E-mail configurado
        $smtp = setting('smtp_host', '');
        $checks[] = ['label' => 'SMTP configurado', 'status' => $smtp ? 'ok' : 'warning', 'detail' => $smtp ? 'Configurado' : 'Não configurado'];

        // Ambiente de produção com display_errors off
        $isProd = Config::isProduction();
        $displayErrors = (bool) Config::get('app.logs.display_errors', false);
        $checks[] = [
            'label'  => 'display_errors em produção',
            'status' => (!$isProd || !$displayErrors) ? 'ok' : 'error',
            'detail' => $isProd ? ($displayErrors ? 'LIGADO (risco!)' : 'Desligado') : 'Ambiente de desenvolvimento',
        ];

        return $checks;
    }
}
