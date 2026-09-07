<?php

namespace App\Services;

/**
 * Serviço de internacionalização (i18n) — preparação.
 *
 * O site nasce em pt-BR, mas toda a camada de textos fica pronta para tradução:
 * as strings ficam em resources/lang/{locale}.php e são resolvidas por chave
 * com notação de ponto ("home.title"). Se a chave não existir, devolve a própria
 * chave, de forma que nada quebra caso uma tradução esteja ausente.
 *
 * O locale ativo vem do setting `locale` (fallback pt-BR).
 */
class Lang
{
    private static ?string $locale = null;
    private static string $fallback = 'pt-BR';

    /** Cache de dicionários por locale já carregado. */
    private static array $loaded = [];

    /**
     * Define o locale ativo explicitamente (ex.: chamada no boot).
     */
    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    /**
     * Retorna o locale ativo, resolvendo a partir do setting se necessário.
     */
    public static function locale(): string
    {
        if (self::$locale !== null) {
            return self::$locale;
        }

        // Evita dependência circular: só lê o setting se possível.
        try {
            $fromSetting = SettingsService::get('locale', self::$fallback);
        } catch (\Throwable $e) {
            $fromSetting = self::$fallback;
        }

        self::$locale = is_string($fromSetting) && $fromSetting !== '' ? $fromSetting : self::$fallback;
        return self::$locale;
    }

    /**
     * Traduz uma chave (notação de ponto). $replace substitui :placeholders.
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?: self::locale();
        $value = self::lookup($locale, $key);

        // Fallback para o idioma padrão caso ausente no locale ativo.
        if ($value === null && $locale !== self::$fallback) {
            $value = self::lookup(self::$fallback, $key);
        }

        // Último recurso: a própria chave.
        if ($value === null) {
            $value = $key;
        }

        foreach ($replace as $search => $val) {
            $value = str_replace(':' . $search, (string) $val, $value);
        }

        return $value;
    }

    /**
     * Resolve uma chave "a.b.c" dentro do dicionário do locale.
     */
    private static function lookup(string $locale, string $key): ?string
    {
        $dict = self::load($locale);
        $segments = explode('.', $key);
        $node = $dict;

        foreach ($segments as $segment) {
            if (is_array($node) && array_key_exists($segment, $node)) {
                $node = $node[$segment];
            } else {
                return null;
            }
        }

        return is_string($node) ? $node : null;
    }

    /**
     * Carrega (com cache) o dicionário de um locale de resources/lang/{locale}.php.
     */
    private static function load(string $locale): array
    {
        if (isset(self::$loaded[$locale])) {
            return self::$loaded[$locale];
        }

        $file = BASE_PATH . '/resources/lang/' . str_replace(['/', '\\', '..'], '', $locale) . '.php';
        $dict = [];
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data)) {
                $dict = $data;
            }
        }

        self::$loaded[$locale] = $dict;
        return $dict;
    }
}
