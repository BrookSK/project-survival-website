<?php

use App\Services\GameApi\UrlGuard;

/**
 * Validação anti-SSRF/open-redirect da URL de download.
 * Nos testes o ambiente é PRODUCTION (sem config/local.php), então HTTPS é
 * obrigatório e hosts privados são bloqueados.
 */
class UrlGuardDownloadTest extends TestCase
{
    public function testHttpsPublicHostIsAllowed(): void
    {
        $this->assertTrue(UrlGuard::isSafeDownloadUrl('https://downloads.example/ProjectSurvivalSetup.exe'));
    }

    public function testHttpIsRejectedInProduction(): void
    {
        // Em produção exige HTTPS.
        $this->assertFalse(UrlGuard::isSafeDownloadUrl('http://downloads.example/setup.exe'));
    }

    public function testPrivateHostIsRejected(): void
    {
        $this->assertFalse(UrlGuard::isSafeDownloadUrl('https://127.0.0.1/setup.exe'));
        $this->assertFalse(UrlGuard::isSafeDownloadUrl('https://localhost/setup.exe'));
    }

    public function testNonHttpSchemeRejected(): void
    {
        $this->assertFalse(UrlGuard::isSafeDownloadUrl('ftp://downloads.example/setup.exe'));
        $this->assertFalse(UrlGuard::isSafeDownloadUrl('file:///etc/passwd'));
        $this->assertFalse(UrlGuard::isSafeDownloadUrl(''));
    }

    public function testAllowlistExactMatch(): void
    {
        $this->assertTrue(UrlGuard::isSafeDownloadUrl('https://cdn.example/setup.exe', ['cdn.example']));
    }

    public function testAllowlistSubdomainMatch(): void
    {
        // downloads.cdn.example é subdomínio de cdn.example.
        $this->assertTrue(UrlGuard::isSafeDownloadUrl('https://downloads.cdn.example/setup.exe', ['cdn.example']));
    }

    public function testAllowlistMiss(): void
    {
        // Host fora da allowlist é rejeitado (mesmo sendo público/https).
        $this->assertFalse(UrlGuard::isSafeDownloadUrl('https://evil.example/setup.exe', ['cdn.example']));
    }

    public function testHostExtraction(): void
    {
        $this->assertEquals('downloads.example', UrlGuard::host('https://downloads.example:443/x'));
        $this->assertTrue(UrlGuard::host('') === null, 'string vazia não tem host');
    }
}
