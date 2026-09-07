<?php

use App\Services\RateLimiter;

/**
 * Testes do limitador de taxa (baseado em arquivo).
 */
class RateLimiterTest extends TestCase
{
    private function key(): string
    {
        return 'test:' . uniqid('', true);
    }

    public function testCountsHits(): void
    {
        $key = $this->key();
        RateLimiter::clear($key);
        $this->assertEquals(1, RateLimiter::hit($key, 60), 'primeiro hit conta 1');
        $this->assertEquals(2, RateLimiter::hit($key, 60), 'segundo hit conta 2');
        $this->assertEquals(2, RateLimiter::attempts($key), 'attempts reflete o total');
        RateLimiter::clear($key);
    }

    public function testTooManyAttempts(): void
    {
        $key = $this->key();
        RateLimiter::clear($key);
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5), 'deve bloquear ao atingir o máximo');
        $this->assertFalse(RateLimiter::tooManyAttempts($key, 10), 'não bloqueia abaixo do máximo');
        RateLimiter::clear($key);
    }

    public function testClearResets(): void
    {
        $key = $this->key();
        RateLimiter::hit($key, 60);
        RateLimiter::clear($key);
        $this->assertEquals(0, RateLimiter::attempts($key), 'clear zera o contador');
    }
}
