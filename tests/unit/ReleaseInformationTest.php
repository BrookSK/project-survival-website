<?php

use App\Services\GameApi\ReleaseInformation;

/**
 * DTO de release: parsing seguro do contrato oficial, sem inventar campos.
 */
class ReleaseInformationTest extends TestCase
{
    private function sample(array $override = []): array
    {
        return array_merge([
            'product'   => 'Project Survival',
            'channel'   => 'stable',
            'version'   => '1.0.0',
            'platform'  => 'windows',
            'mandatory' => false,
            'notes'     => 'Primeira versão.',
            'released_at' => '2026-09-07T00:00:00Z',
            'requirements' => ['os' => 'Windows 10/11', 'arch' => 'x86_64', 'graphics' => 'OpenGL 3.3', 'internet' => 'Não obrigatória'],
            'installer' => [
                'filename' => 'ProjectSurvivalSetup.exe',
                'url' => 'https://downloads.example/1.0.0/ProjectSurvivalSetup.exe',
                'permanent_url' => 'https://downloads.example/ProjectSurvivalSetup.exe',
                'sha256' => '96286c0101087bd5d3b0be281275f45d8bf4c3b4d8b88954ab9bd547b1b65619',
                'size' => 220116464,
            ],
            'game' => ['filename' => 'game.zip', 'url' => 'https://downloads.example/1.0.0/game.zip'],
            'download_url' => 'https://downloads.example/ProjectSurvivalSetup.exe',
        ], $override);
    }

    public function testParsesOfficialFields(): void
    {
        $r = ReleaseInformation::fromApi($this->sample());
        $this->assertEquals('1.0.0', $r->version);
        $this->assertEquals('windows', $r->platform);
        $this->assertEquals('stable', $r->channel);
        $this->assertEquals('Windows 10/11', $r->requirements['os']);
        $this->assertEquals(220116464, $r->sizeBytes());
        $this->assertTrue($r->isUsable());
    }

    public function testBestInstallerUrlPrefersDownloadUrl(): void
    {
        $r = ReleaseInformation::fromApi($this->sample());
        // download_url tem prioridade.
        $this->assertEquals('https://downloads.example/ProjectSurvivalSetup.exe', $r->bestInstallerUrl());
    }

    public function testBestInstallerUrlFallsBackToPermanentThenUrl(): void
    {
        $r = ReleaseInformation::fromApi($this->sample(['download_url' => null]));
        $this->assertEquals('https://downloads.example/ProjectSurvivalSetup.exe', $r->bestInstallerUrl(), 'sem download_url usa permanent_url');

        $data = $this->sample(['download_url' => null]);
        $data['installer']['permanent_url'] = null;
        $r2 = ReleaseInformation::fromApi($data);
        $this->assertEquals('https://downloads.example/1.0.0/ProjectSurvivalSetup.exe', $r2->bestInstallerUrl(), 'sem permanent usa url versionada');
    }

    public function testSizeLabelFormatsMb(): void
    {
        $r = ReleaseInformation::fromApi($this->sample());
        // 220116464 bytes ~ 210 MB.
        $this->assertEquals('210 MB', $r->sizeLabel());
    }

    public function testNotUsableWithoutVersionOrUrl(): void
    {
        $noVersion = ReleaseInformation::fromApi($this->sample(['version' => '']));
        $this->assertFalse($noVersion->isUsable());

        $data = $this->sample(['download_url' => null]);
        $data['installer'] = [];
        $noUrl = ReleaseInformation::fromApi($data);
        $this->assertFalse($noUrl->isUsable(), 'sem nenhuma URL de instalador não é usável');
    }

    public function testNeverInventsFields(): void
    {
        // Payload mínimo/vazio não deve inventar versão nem URL.
        $r = ReleaseInformation::fromApi([]);
        $this->assertEquals('', $r->version);
        $this->assertTrue($r->bestInstallerUrl() === null);
        $this->assertFalse($r->isUsable());
    }
}
