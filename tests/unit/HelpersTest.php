<?php

/**
 * Testes de helpers globais e i18n.
 */
class HelpersTest extends TestCase
{
    public function testStrSlugBasic(): void
    {
        $this->assertEquals('ola-mundo', str_slug('Olá Mundo'), 'acentos e espaços viram slug');
    }

    public function testStrSlugTrimsSymbols(): void
    {
        $this->assertEquals('a-b-c', str_slug('  a / b / c  '), 'símbolos viram hífen único');
    }

    public function testEscapeHtml(): void
    {
        $this->assertStringNotContains('<script', e('<script>alert(1)</script>'), 'e() escapa HTML');
    }

    public function testLangFallbackToKey(): void
    {
        // Chave inexistente deve devolver a própria chave.
        $this->assertEquals('nao.existe.essa.chave', __('nao.existe.essa.chave'), 'chave ausente retorna a chave');
    }

    public function testLangResolvesKnownKey(): void
    {
        // 'common.read_more' existe no dicionário pt-BR base.
        \App\Services\Lang::setLocale('pt-BR');
        $this->assertEquals('Ler mais', __('common.read_more'), 'resolve chave conhecida em pt-BR');
    }

    public function testLangReplacePlaceholder(): void
    {
        \App\Services\Lang::setLocale('pt-BR');
        $out = __('news.published_at', ['date' => '01/01/2026']);
        $this->assertStringContains('01/01/2026', $out, 'substitui :date');
    }
}
