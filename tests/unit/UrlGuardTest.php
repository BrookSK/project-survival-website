<?php

use App\Services\GameApi\UrlGuard;

/**
 * Testa a validação de URL base e a proteção SSRF.
 * Em ambiente de teste (não produção), localhost é permitido.
 */
class UrlGuardTest extends TestCase
{
    public function testLocalhostDependsOnEnvironment(): void
    {
        // Em produção o localhost é bloqueado (anti-SSRF); em dev é permitido.
        $errors = UrlGuard::validateBaseUrl('http://localhost:4000/api/v1');
        if (\App\Core\Config::isProduction()) {
            $this->assertTrue(count($errors) > 0, 'localhost deve ser bloqueado em produção');
        } else {
            $this->assertEquals([], $errors, 'localhost deve ser aceito em desenvolvimento');
        }
    }

    public function testAcceptsHttps(): void
    {
        $errors = UrlGuard::validateBaseUrl('https://api.exemplo.com/api/v1');
        $this->assertEquals([], $errors, 'https público deve ser aceito em qualquer ambiente');
    }

    public function testRejectsEmpty(): void
    {
        $this->assertTrue(count(UrlGuard::validateBaseUrl('')) > 0, 'URL vazia deve falhar');
    }

    public function testRejectsMissingScheme(): void
    {
        $this->assertTrue(count(UrlGuard::validateBaseUrl('api.exemplo.com/api/v1')) > 0, 'sem esquema deve falhar');
    }

    public function testRejectsNonHttpScheme(): void
    {
        $this->assertTrue(count(UrlGuard::validateBaseUrl('ftp://x/api')) > 0, 'esquema não http(s) deve falhar');
    }

    public function testNormalizeStripsTrailingSlash(): void
    {
        $this->assertEquals('http://x/api/v1', UrlGuard::normalizeBaseUrl('http://x/api/v1/'), 'remove barra final');
    }

    public function testPrivateHostDetection(): void
    {
        $this->assertTrue(UrlGuard::isPrivateHost('127.0.0.1'), '127.0.0.1 é privado');
        $this->assertTrue(UrlGuard::isPrivateHost('localhost'), 'localhost é privado');
        $this->assertTrue(UrlGuard::isPrivateHost('10.0.0.5'), '10/8 é privado');
        $this->assertTrue(UrlGuard::isPrivateHost('192.168.1.1'), '192.168/16 é privado');
        $this->assertFalse(UrlGuard::isPrivateHost('8.8.8.8'), 'IP público não é privado');
    }
}
