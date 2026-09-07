<?php

namespace App\Services;

use App\Core\Database;

/**
 * Serviço centralizado de configurações administráveis (tabela `settings`).
 *
 * Substitui o uso de .env para dados NÃO sensíveis de infraestrutura.
 * Faz cache em memória durante a requisição e converte tipos automaticamente.
 */
class SettingsService
{
    private static array $cache = [];
    private static bool $loaded = false;

    /**
     * Carrega todas as configurações uma vez por requisição.
     */
    private static function loadAll(): void
    {
        if (self::$loaded) {
            return;
        }

        try {
            $rows = Database::getInstance()->fetchAll(
                "SELECT `group`, `key`, `value`, `type` FROM `settings`"
            );
        } catch (\Throwable $e) {
            // Antes da instalação a tabela pode não existir.
            self::$loaded = true;
            return;
        }

        foreach ($rows as $row) {
            self::$cache[$row['key']] = [
                'group' => $row['group'],
                'value' => self::castValue($row['value'], $row['type']),
            ];
        }

        self::$loaded = true;
    }

    /**
     * Recupera uma configuração pela chave.
     */
    public static function get(string $key, $default = null)
    {
        self::loadAll();
        return self::$cache[$key]['value'] ?? $default;
    }

    /**
     * Retorna todas as configurações de um grupo como key => value.
     */
    public static function group(string $group): array
    {
        self::loadAll();
        $result = [];
        foreach (self::$cache as $key => $data) {
            if ($data['group'] === $group) {
                $result[$key] = $data['value'];
            }
        }
        return $result;
    }

    /**
     * Persiste um valor de configuração e atualiza o cache.
     */
    public static function set(string $key, $value): bool
    {
        $db = Database::getInstance();

        $stored = is_bool($value) ? ($value ? '1' : '0')
            : (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value);

        $affected = $db->execute(
            "UPDATE `settings` SET `value` = :value WHERE `key` = :key",
            ['value' => $stored, 'key' => $key]
        );

        // Atualiza cache
        if (isset(self::$cache[$key])) {
            self::$cache[$key]['value'] = $value;
        }

        return $affected >= 0;
    }

    /**
     * Persiste múltiplos valores de uma vez.
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Converte o valor armazenado (string) para o tipo apropriado.
     */
    private static function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return $value === '1' || $value === 'true';
            case 'json':
                $decoded = json_decode($value, true);
                return $decoded ?? [];
            default:
                return $value;
        }
    }

    /**
     * Limpa o cache (útil após atualizações em lote).
     */
    public static function flush(): void
    {
        self::$cache = [];
        self::$loaded = false;
    }
}
