<?php

namespace App\Services;

use App\Core\Logger;

/**
 * Rate limiter simples baseado em arquivos (sem Redis).
 *
 * Armazena contadores em storage/cache/ratelimit/. Cada chave (ex.: uma ação
 * + IP) tem um contador com janela de tempo. Funciona em hospedagem PHP comum.
 *
 * Uso:
 *   if (RateLimiter::tooManyAttempts('contact:' . $ip, 5, 600)) { ... bloquear ... }
 *   RateLimiter::hit('contact:' . $ip, 600);
 */
class RateLimiter
{
    private static function dir(): string
    {
        $dir = CACHE_PATH . '/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.json';
    }

    private static function read(string $key): array
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return ['count' => 0, 'reset' => 0];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data) || !isset($data['reset'])) {
            return ['count' => 0, 'reset' => 0];
        }
        // Janela expirada: zera
        if ($data['reset'] < time()) {
            return ['count' => 0, 'reset' => 0];
        }
        return $data;
    }

    private static function write(string $key, array $data): void
    {
        @file_put_contents(self::file($key), json_encode($data), LOCK_EX);
    }

    /**
     * Registra uma tentativa dentro da janela de $decaySeconds.
     */
    public static function hit(string $key, int $decaySeconds = 600): int
    {
        $data = self::read($key);
        if ($data['reset'] === 0) {
            $data['reset'] = time() + $decaySeconds;
            $data['count'] = 0;
        }
        $data['count']++;
        self::write($key, $data);
        return $data['count'];
    }

    public static function attempts(string $key): int
    {
        return self::read($key)['count'];
    }

    /**
     * Verifica se excedeu o máximo de tentativas na janela.
     */
    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return self::attempts($key) >= $maxAttempts;
    }

    /**
     * Segundos restantes até a liberação (0 se não bloqueado).
     */
    public static function availableIn(string $key): int
    {
        $data = self::read($key);
        return $data['reset'] > time() ? ($data['reset'] - time()) : 0;
    }

    public static function clear(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Limpa arquivos de rate limit expirados (manutenção).
     */
    public static function purgeExpired(): int
    {
        $removed = 0;
        $files = glob(self::dir() . '/*.json') ?: [];
        foreach ($files as $file) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (!is_array($data) || ($data['reset'] ?? 0) < time()) {
                if (@unlink($file)) {
                    $removed++;
                }
            }
        }
        return $removed;
    }
}
