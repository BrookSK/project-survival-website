<?php

namespace App\Core;

/**
 * Abstração da resposta HTTP.
 *
 * Aplica headers de segurança padrão e oferece atalhos para HTML, JSON,
 * redirecionamento e códigos de status.
 */
class Response
{
    /**
     * Aplica headers de segurança recomendados.
     */
    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header_remove('X-Powered-By');

        // Content-Security-Policy compatível com os recursos realmente usados:
        // - assets próprios (self)
        // - inline styles/scripts (o front usa alguns handlers/estilos inline)
        // - imagens de uploads (self) + data: (previews)
        // - embeds de vídeo (YouTube/Vimeo) via frame-src
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "frame-src https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];
        header('Content-Security-Policy: ' . implode('; ', $csp));

        if (Config::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }

    public static function html(string $content, int $status = 200): void
    {
        self::status($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    public static function json(array $data, int $status = 200): void
    {
        self::status($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        self::status($status);
        header('Location: ' . $url);
        exit;
    }

    public static function back(string $fallback = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        self::redirect($referer);
    }
}
