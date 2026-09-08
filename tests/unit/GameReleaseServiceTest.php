<?php

use App\Core\Database;
use App\Services\GameApi\GameApiClient;
use App\Services\GameApi\GameReleaseService;
use App\Services\SettingsService;

/**
 * GameReleaseService: consome /public/download/{channel} via GameApiClient
 * (mockado). Cache desabilitado nos testes para determinismo.
 */
class GameReleaseServiceTest extends TestCase
{
    private function boot(): void
    {
        $db = new StubDatabase();
        // Integração habilitada, HTTPS (produção exige), cache desligado.
        $db->setSettings([
            'game_api_enabled'       => ['value' => '1', 'type' => 'boolean', 'group' => 'game_api'],
            'game_api_base_url'      => ['value' => 'https://api.jogo.example/api/v1', 'group' => 'game_api'],
            'game_api_cache_enabled' => ['value' => '0', 'type' => 'boolean', 'group' => 'game_api_cache'],
            'release_channel'        => ['value' => 'stable', 'group' => 'releases'],
        ]);
        Database::setInstance($db);
        SettingsService::flush();
    }

    private function service(MockHttpClient $http): GameReleaseService
    {
        $client = new GameApiClient('https://api.jogo.example/api/v1', '', 8, true, $http);
        return new GameReleaseService($client);
    }

    private function releasePayload(): array
    {
        return [
            'product' => 'Project Survival', 'channel' => 'stable', 'version' => '1.0.0',
            'platform' => 'windows', 'mandatory' => false, 'notes' => 'ok',
            'installer' => [
                'filename' => 'ProjectSurvivalSetup.exe',
                'url' => 'https://downloads.example/1.0.0/ProjectSurvivalSetup.exe',
                'permanent_url' => 'https://downloads.example/ProjectSurvivalSetup.exe',
                'sha256' => 'abc123', 'size' => 220116464,
            ],
            'download_url' => 'https://downloads.example/ProjectSurvivalSetup.exe',
        ];
    }

    public function testLatestReturnsUsableRelease(): void
    {
        $this->boot();
        $http = new MockHttpClient();
        $http->pushJson(200, ['success' => true, 'data' => $this->releasePayload()]);

        $release = $this->service($http)->latest();

        $this->assertTrue($release !== null, 'deve retornar release');
        $this->assertEquals('1.0.0', $release->version);
        $this->assertTrue($release->isUsable());
        $this->assertEquals('https://downloads.example/ProjectSurvivalSetup.exe', $release->bestInstallerUrl());
    }

    public function testConsumesDownloadEndpointForChannel(): void
    {
        $this->boot();
        $http = new MockHttpClient();
        $http->pushJson(200, ['success' => true, 'data' => $this->releasePayload()]);

        $this->service($http)->forChannel('beta');

        // Confirma que o endpoint oficial /public/download/{channel} foi chamado.
        $last = end($http->calls);
        $this->assertTrue(strpos((string) $last['url'], '/public/download/beta') !== false, 'deve chamar /public/download/beta');
    }

    public function testApiUnavailableReturnsNullWithoutStale(): void
    {
        $this->boot();
        GameReleaseService::flushCache(); // garante ausência de stale
        $http = new MockHttpClient();
        $http->pushTransportError('timeout');

        $release = $this->service($http)->latest();
        // Sem cache stale e API indisponível -> null (nunca inventa versão).
        $this->assertTrue($release === null, 'sem release e sem stale deve ser null');
    }

    public function testReleaseNotFoundReturnsNull(): void
    {
        $this->boot();
        GameReleaseService::flushCache();
        $http = new MockHttpClient();
        $http->pushJson(404, ['success' => false, 'error' => ['code' => 'RELEASE_NOT_FOUND', 'message' => 'sem release']]);

        $release = $this->service($http)->latest();
        $this->assertTrue($release === null);
    }
}
