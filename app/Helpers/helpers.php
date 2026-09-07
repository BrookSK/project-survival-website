<?php

/**
 * Funções auxiliares globais.
 *
 * Carregadas no bootstrap. Fornecem atalhos de uso frequente nas views e
 * controllers (escaping, URLs, CSRF, settings, old input, etc.).
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

if (!function_exists('e')) {
    /**
     * Escapa uma string para saída segura em HTML (proteção XSS).
     */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Gera uma URL absoluta a partir do site_url configurado.
     */
    function url(string $path = ''): string
    {
        $base = rtrim((string) setting('site_url', ''), '/');
        if ($base === '') {
            // Fallback: deriva do host atual
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $scheme . '://' . $host;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * URL para um asset em public/assets.
     */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('uploaded')) {
    /**
     * URL para um arquivo em public/uploads.
     */
    function uploaded(string $path): string
    {
        if ($path === '') {
            return '';
        }
        return url('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::input();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('method_field')) {
    /**
     * Campo hidden para spoofing de método (PUT/PATCH/DELETE em forms).
     */
    function method_field(string $method): string
    {
        $method = e(strtoupper($method));
        return "<input type=\"hidden\" name=\"_method\" value=\"{$method}\">";
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = ''): string
    {
        return e(Session::old($key, $default));
    }
}

if (!function_exists('setting')) {
    /**
     * Recupera uma configuração administrável (tabela settings) via SettingsService.
     */
    function setting(string $key, $default = null)
    {
        return \App\Services\SettingsService::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        \App\Core\Response::redirect($url);
    }
}

if (!function_exists('errors')) {
    /**
     * Erros de validação da última requisição (flash).
     */
    function errors(): array
    {
        $errors = Session::get('__errors', []);
        Session::remove('__errors');
        return is_array($errors) ? $errors : [];
    }
}

if (!function_exists('has_permission')) {
    function has_permission(string $permission): bool
    {
        return \App\Services\AuthService::can($permission);
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return \App\Services\AuthService::user();
    }
}

if (!function_exists('partial')) {
    function partial(string $view, array $data = []): string
    {
        return View::partial($view, $data);
    }
}

if (!function_exists('str_slug')) {
    /**
     * Gera um slug amigável para URLs a partir de um texto.
     */
    function str_slug(string $text): string
    {
        $text = trim($text);
        // Transliteração básica de acentos
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = strtolower($text);
        // Remove artefatos de transliteração (ex.: aspas/til soltos) antes de normalizar
        $text = preg_replace('/[\'"~^`]/', '', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return $text === '' ? 'item' : $text;
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if (!$datetime) {
            return '-';
        }
        $ts = strtotime($datetime);
        return $ts ? date($format, $ts) : '-';
    }
}

if (!function_exists('str_length')) {
    /**
     * Comprimento de string multibyte, com fallback quando mbstring ausente.
     */
    function str_length(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }
}

if (!function_exists('str_sub')) {
    /**
     * Substring multibyte, com fallback quando mbstring ausente.
     */
    function str_sub(string $text, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, $start, $length);
        }
        return $length === null ? substr($text, $start) : substr($text, $start, $length);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(string $text, int $length = 160): string
    {
        $text = trim(strip_tags($text));
        if (str_length($text) <= $length) {
            return $text;
        }
        return str_sub($text, 0, $length) . '…';
    }
}

if (!function_exists('__')) {
    /**
     * Traduz uma chave de idioma (i18n). Ex.: __('common.read_more').
     *
     * @param string $key      Chave em notação de ponto.
     * @param array  $replace  Substituições de :placeholders.
     */
    function __(string $key, array $replace = []): string
    {
        return \App\Services\Lang::get($key, $replace);
    }
}

if (!function_exists('locale')) {
    /**
     * Retorna o locale ativo (ex.: pt-BR).
     */
    function locale(): string
    {
        return \App\Services\Lang::locale();
    }
}
