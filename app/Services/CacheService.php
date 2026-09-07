<?php

namespace App\Services;

/**
 * Cache simples baseado em arquivos.
 *
 * Não requer Redis/Memcached — adequado para hospedagem PHP compartilhada.
 * Armazena valores serializados em storage/cache/data com TTL. Usado para
 * dados de leitura frequente e baixo churn (settings, menus, sitemap).
 *
 * Degrada graciosamente: se o diretório não puder ser criado/escrito, os
 * métodos de leitura devolvem o default e os de escrita retornam false, sem
 * lançar exceção — a aplicação continua funcionando sem cache.
 */
class CacheService
{
    /**
     * Diretório onde os arquivos de cache são gravados.
     */
    private static function dir(): string
    {
        return CACHE_PATH . '/data';
    }

    /**
     * Garante a existência do diretório de cache.
     */
    private static function ensureDir(): bool
    {
        $dir = self::dir();
        if (is_dir($dir)) {
            return true;
        }
        return @mkdir($dir, 0775, true) && is_dir($dir);
    }

    /**
     * Caminho absoluto do arquivo de cache para uma chave.
     */
    private static function path(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.cache';
    }

    /**
     * Recupera um valor do cache. Retorna $default se ausente ou expirado.
     */
    public static function get(string $key, $default = null)
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $payload = @unserialize($raw);
        if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('value', $payload)) {
            return $default;
        }

        // expires === 0 significa "sem expiração"
        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $payload['value'];
    }

    /**
     * Verifica se existe um valor válido (não expirado) para a chave.
     */
    public static function has(string $key): bool
    {
        return self::get($key, self::class) !== self::class;
    }

    /**
     * Armazena um valor no cache por $ttl segundos (0 = sem expiração).
     */
    public static function put(string $key, $value, int $ttl = 300): bool
    {
        if (!self::ensureDir()) {
            return false;
        }

        $payload = [
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value'   => $value,
        ];

        $file = self::path($key);
        $tmp = $file . '.' . uniqid('', true) . '.tmp';

        if (@file_put_contents($tmp, serialize($payload), LOCK_EX) === false) {
            return false;
        }

        // Rename atômico para evitar leitura de arquivo parcial.
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }

        return true;
    }

    /**
     * Retorna o valor em cache ou executa o callback, armazenando o resultado.
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $sentinel = self::class;
        $cached = self::get($key, $sentinel);
        if ($cached !== $sentinel) {
            return $cached;
        }

        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    /**
     * Remove uma entrada específica do cache.
     */
    public static function forget(string $key): bool
    {
        $file = self::path($key);
        if (is_file($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Limpa todo o cache de dados. Retorna a quantidade de arquivos removidos.
     */
    public static function flush(): int
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Remove entradas expiradas (chamável por cron/limpeza periódica).
     */
    public static function purgeExpired(): int
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }
            $payload = @unserialize($raw);
            if (!is_array($payload) || !isset($payload['expires'])) {
                @unlink($file);
                $count++;
                continue;
            }
            if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }
}
