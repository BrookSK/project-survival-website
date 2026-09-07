<?php

use App\Services\GameApi\ErrorPresenter;
use App\Services\GameApi\Exceptions\ApiUnavailableException;
use App\Services\GameApi\Exceptions\AuthenticationException;
use App\Services\GameApi\Exceptions\RateLimitException;

/**
 * Testa a apresentação amigável de erros (sem vazar detalhes técnicos).
 */
class ErrorPresenterTest extends TestCase
{
    public function testUnavailableUsesContext(): void
    {
        $msg = ErrorPresenter::message(new ApiUnavailableException('boom', 'X', 0), 'A loja');
        $this->assertStringContains('A loja', $msg, 'inclui o contexto');
        $this->assertStringNotContains('boom', $msg, 'não vaza a mensagem técnica interna');
    }

    public function testForbiddenMessage(): void
    {
        $e = new AuthenticationException('x', 'FORBIDDEN', 403);
        $this->assertStringContains('permissão', ErrorPresenter::message($e), '403 fala de permissão');
    }

    public function testRateLimitMentionsWait(): void
    {
        $e = new RateLimitException('muitas', 'RATE_LIMITED', 429, null, 15);
        $msg = ErrorPresenter::message($e);
        $this->assertStringContains('15', $msg, 'menciona os segundos de espera');
    }
}
