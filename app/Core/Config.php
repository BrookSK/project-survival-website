<?php

namespace App\Core;

/**
 * Acesso centralizado à configuração da aplicação (config/config.php e database.php).
 *
 * Carrega os arrays de configuração uma única vez e permite acesso por
 * notação de ponto: Config::get('session.lifetime').
 */
class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        self::$items['app'] = require CONFIG_PATH . '/config.php';
        self::$items['database'] = require CONFIG_PATH . '/database.php';
        self::$loaded = true;
    }

    /**
     * Recupera um valor por notação de ponto. Ex.: 'app.session.lifetime'.
     * O primeiro segmento é o arquivo (app|database).
     */
    public static function get(string $key, $default = null)
    {
        self::load();

        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function all(): array
    {
        self::load();
        return self::$items;
    }

    public static function isProduction(): bool
    {
        return self::get('app.environment') === ENV_PRODUCTION;
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('app.debug', false);
    }
}
