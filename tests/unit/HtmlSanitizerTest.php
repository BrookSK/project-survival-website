<?php

use App\Services\HtmlSanitizer;

/**
 * Testes do sanitizador de HTML — foco em prevenção de XSS.
 */
class HtmlSanitizerTest extends TestCase
{
    public function testRemovesScriptTag(): void
    {
        $out = HtmlSanitizer::clean('<p>ok</p><script>alert(1)</script>');
        $this->assertStringNotContains('<script', $out, 'deve remover <script>');
        $this->assertStringContains('ok', $out, 'deve preservar o texto legítimo');
    }

    public function testRemovesEventHandlers(): void
    {
        $out = HtmlSanitizer::clean('<a href="/x" onclick="evil()">link</a>');
        $this->assertStringNotContains('onclick', $out, 'deve remover atributos on*');
    }

    public function testRemovesJavascriptUrl(): void
    {
        $out = HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContains('javascript:', $out, 'deve remover href javascript:');
    }

    public function testKeepsAllowedFormatting(): void
    {
        $out = HtmlSanitizer::clean('<p><strong>Negrito</strong> e <em>itálico</em></p>');
        $this->assertStringContains('<strong>', $out, 'deve manter <strong>');
        $this->assertStringContains('<em>', $out, 'deve manter <em>');
    }

    public function testExternalLinkGetsRelNoopener(): void
    {
        $out = HtmlSanitizer::clean('<a href="https://exemplo.com" target="_blank">x</a>');
        $this->assertStringContains('noopener', $out, 'target=_blank deve receber rel=noopener');
    }

    public function testNullBecomesEmptyString(): void
    {
        $this->assertEquals('', HtmlSanitizer::clean(null), 'null vira string vazia');
    }
}
