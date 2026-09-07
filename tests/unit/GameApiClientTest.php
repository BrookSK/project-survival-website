<?php

use App\Services\GameApi\Exceptions\ApiUnavailableException;
use App\Services\GameApi\Exceptions\AuthenticationException;
use App\Services\GameApi\Exceptions\ConflictException;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\Exceptions\NotFoundException;
use App\Services\GameApi\Exceptions\RateLimitException;
use App\Services\GameApi\Exceptions\ValidationException;
use App\Services\GameApi\GameApiClient;

/**
 * Testa o GameApiClient: interpretação de envelope e mapeamento de status.
 * Usa MockHttpClient (sem rede, sem DB).
 */
class GameApiClientTest extends TestCase
{
    private function client(MockHttpClient $mock): GameApiClient
    {
        return new GameApiClient('http://localhost:4000/api/v1', 'cid-teste', 8, true, $mock);
    }

    public function testSuccessReturnsData(): void
    {
        $mock = (new MockHttpClient())->pushJson(200, ['success' => true, 'data' => ['x' => 1]]);
        $data = $this->client($mock)->get('/whatever');
        $this->assertEquals(['x' => 1], $data, 'sucesso deve devolver o data do envelope');
    }

    public function testSendsBearerToken(): void
    {
        $mock = (new MockHttpClient())->pushJson(200, ['success' => true, 'data' => []]);
        $this->client($mock)->get('/me', [], 'tok-123');
        $this->assertEquals('Bearer tok-123', $mock->lastAuthHeader(), 'deve enviar Authorization Bearer');
    }

    public function testValidation400(): void
    {
        $mock = (new MockHttpClient())->pushJson(400, ['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'inválido']]);
        $this->expect($mock, ValidationException::class);
    }

    public function testAuth401(): void
    {
        $mock = (new MockHttpClient())->pushJson(401, ['success' => false, 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'sem token']]);
        $this->expect($mock, AuthenticationException::class);
    }

    public function testForbidden403IsAuth(): void
    {
        $mock = (new MockHttpClient())->pushJson(403, ['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'x']]);
        $e = $this->capture($mock);
        $this->assertTrue($e instanceof AuthenticationException, '403 deve ser AuthenticationException');
        $this->assertEquals(403, $e ? $e->httpStatus() : 0, 'status 403 preservado');
    }

    public function testNotFound404(): void
    {
        $mock = (new MockHttpClient())->pushJson(404, ['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'x']]);
        $this->expect($mock, NotFoundException::class);
    }

    public function testConflict409(): void
    {
        $mock = (new MockHttpClient())->pushJson(409, ['success' => false, 'error' => ['code' => 'ALREADY_USED', 'message' => 'usado']]);
        $this->expect($mock, ConflictException::class);
    }

    public function testRateLimit429(): void
    {
        $mock = (new MockHttpClient())->pushJson(429, ['success' => false, 'error' => ['code' => 'RATE_LIMITED', 'message' => 'aguarde']], ['retry-after' => '30']);
        $e = $this->capture($mock);
        $this->assertTrue($e instanceof RateLimitException, '429 deve ser RateLimitException');
        $this->assertEquals(30, ($e instanceof RateLimitException) ? $e->retryAfter() : 0, 'retry-after capturado');
    }

    public function testServerError500IsUnavailable(): void
    {
        $mock = (new MockHttpClient())->pushJson(500, ['success' => false, 'error' => ['code' => 'X', 'message' => 'boom']]);
        $this->expect($mock, ApiUnavailableException::class);
    }

    public function testTransportErrorIsUnavailable(): void
    {
        $mock = (new MockHttpClient())->pushTransportError('timeout');
        $this->expect($mock, ApiUnavailableException::class);
    }

    public function testInvalidJsonThrows(): void
    {
        $mock = (new MockHttpClient())->pushRaw(200, '<html>not json</html>');
        $e = $this->capture($mock);
        $this->assertTrue($e instanceof GameApiException, 'JSON inválido deve lançar GameApiException');
        $this->assertFalse($e instanceof ApiUnavailableException, 'JSON inválido não é indisponibilidade');
    }

    public function testDisabledIntegrationThrowsUnavailable(): void
    {
        $mock = new MockHttpClient();
        $client = new GameApiClient('http://localhost:4000/api/v1', '', 8, false, $mock);
        $threw = false;
        try {
            $client->get('/news');
        } catch (ApiUnavailableException $e) {
            $threw = true;
        } catch (\Throwable $e) {
            // outro tipo = falha
        }
        $this->assertTrue($threw, 'integração desabilitada deve lançar ApiUnavailableException');
        $this->assertEquals(0, count($mock->calls), 'não deve chamar o HTTP quando desabilitada');
    }

    // ---- helpers ----

    private function expect(MockHttpClient $mock, string $exceptionClass): void
    {
        $e = $this->capture($mock);
        $this->assertTrue($e instanceof $exceptionClass, 'esperava ' . $exceptionClass . ', obteve ' . ($e ? get_class($e) : 'nenhuma'));
    }

    private function capture(MockHttpClient $mock): ?GameApiException
    {
        try {
            $this->client($mock)->get('/x');
        } catch (GameApiException $e) {
            return $e;
        }
        return null;
    }
}
