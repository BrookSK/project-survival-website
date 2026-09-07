<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\CacheService;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameConfigService;
use App\Services\GameApi\GameContentService;
use App\Services\GameApi\GameHealthService;
use App\Services\GameApi\GameStoreService;
use App\Services\GameApi\UrlGuard;
use App\Services\SettingsService;

/**
 * Painel de integração com a API do jogo (Admin → Integrações → API do Jogo).
 *
 * Mostra status (via health check), a configuração de conexão e permite testar
 * a conexão, salvar a base URL/client id/timeout (com validação anti-SSRF) e
 * limpar o cache de dados públicos da API. Usa as permissões de sistema
 * existentes (system.view / system.manage).
 *
 * Nunca expõe segredos: só há client_id público e URL — não há tokens aqui.
 */
class IntegrationController extends Controller
{
    /** Guarda o horário/resultado da última verificação para observabilidade. */
    private const LAST_CHECK_KEY = 'gameapi:diag:last_check';

    public function index(Request $request): void
    {
        $this->authorize('system.view');

        $health = (new GameHealthService())->check();

        // Persiste a última verificação (sem dados sensíveis) para exibição.
        CacheService::put(self::LAST_CHECK_KEY, [
            'at'         => date('c'),
            'online'     => $health['online'],
            'latency_ms' => $health['latency_ms'],
            'version'    => $health['api_version'],
            'database'   => $health['database'],
            'error'      => $health['error'],
        ], 0);

        $this->viewAdmin('admin.integration.index', [
            'title'       => 'Integração com a API do Jogo',
            'breadcrumbs' => [['label' => 'Integrações'], ['label' => 'API do Jogo']],
            'enabled'     => GameApiConfig::isEnabled(),
            'baseUrl'     => GameApiConfig::baseUrl(),
            'clientId'    => GameApiConfig::clientId(),
            'timeout'     => GameApiConfig::timeout(),
            'cacheEnabled'=> GameApiConfig::cacheEnabled(),
            'health'      => $health,
        ]);
    }

    /**
     * Testa a conexão executando GET /health e exibindo o resultado.
     */
    public function test(Request $request): void
    {
        $this->authorize('system.view');
        $this->verifyCsrf($request);

        $health = (new GameHealthService())->check();

        if ($health['online']) {
            $parts = ['API acessível'];
            if ($health['database']) {
                $parts[] = 'banco ' . $health['database'];
            }
            if ($health['api_version']) {
                $parts[] = $health['api_version'];
            }
            $parts[] = $health['latency_ms'] . 'ms';
            Session::flash('success', '✓ ' . implode(' · ', $parts));
        } else {
            Session::flash('error', '✗ ' . ($health['error'] ?: 'API inacessível.'));
        }

        AuditService::log('test', 'integration', null, 'Testou conexão com a API do jogo');
        $this->redirect('/admin/integracoes');
    }

    /**
     * Salva a configuração de conexão (com validação anti-SSRF da URL).
     */
    public function saveConnection(Request $request): void
    {
        $this->authorize('system.manage');
        $this->verifyCsrf($request);

        $baseUrl = trim((string) $request->post('game_api_base_url', ''));
        $clientId = trim((string) $request->post('game_api_client_id', ''));
        $timeout = (int) $request->post('game_api_timeout', 8);
        $enabled = $request->post('game_api_enabled') ? '1' : '0';

        // Validação da URL (protocolo, host, HTTPS/rede privada em produção).
        $urlErrors = UrlGuard::validateBaseUrl($baseUrl);
        if ($urlErrors) {
            Session::flash('error', implode(' ', $urlErrors));
            $this->redirect('/admin/integracoes');
            return;
        }

        $timeout = max(1, min($timeout, 30));

        SettingsService::set('game_api_base_url', UrlGuard::normalizeBaseUrl($baseUrl));
        SettingsService::set('game_api_client_id', $clientId);
        SettingsService::set('game_api_timeout', (string) $timeout);
        SettingsService::set('game_api_enabled', $enabled);
        SettingsService::flush();

        AuditService::log('update', 'integration', null, 'Atualizou a conexão com a API do jogo');
        Session::flash('success', 'Configuração de conexão salva.');
        $this->redirect('/admin/integracoes');
    }

    /**
     * Limpa o cache de dados públicos da API (tudo ou um recurso específico).
     */
    public function clearCache(Request $request): void
    {
        $this->authorize('system.manage');
        $this->verifyCsrf($request);

        $scope = (string) $request->post('scope', 'all');

        switch ($scope) {
            case 'news':
                GameContentService::flushCache('news');
                break;
            case 'events':
                GameContentService::flushCache('events');
                break;
            case 'store':
                GameStoreService::flushCache();
                break;
            case 'config':
                GameConfigService::flushCache();
                break;
            case 'all':
            default:
                $scope = 'all';
                GameContentService::flushCache();
                GameStoreService::flushCache();
                GameConfigService::flushCache();
                break;
        }

        AuditService::log('cache', 'integration', null, "Limpou cache da API do jogo ({$scope})");
        Session::flash('success', 'Cache da API limpo (' . $scope . ').');
        $this->redirect('/admin/integracoes');
    }
}
