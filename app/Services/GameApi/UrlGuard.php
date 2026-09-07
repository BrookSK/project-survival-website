<?php

namespace App\Services\GameApi;

use App\Core\Config;

/**
 * Validação da URL base da API e proteção contra SSRF.
 *
 * A URL base é administrável no painel, então precisa ser validada antes do uso:
 *  - protocolo http/https (https obrigatório em produção);
 *  - host presente e bem-formado;
 *  - em produção, bloqueio de loopback, link-local e faixas de rede privada
 *    (evita que uma configuração maliciosa faça o servidor acessar recursos
 *    internos). Em desenvolvimento, localhost é explicitamente permitido para
 *    apontar à API rodando localmente (http://localhost:4000/api/v1).
 */
class UrlGuard
{
    /**
     * Valida a URL base. Retorna lista de mensagens de erro (vazia = ok).
     */
    public static function validateBaseUrl(string $url): array
    {
        $errors = [];
        $url = trim($url);

        if ($url === '') {
            return ['Informe a URL base da API.'];
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return ['URL base inválida. Use algo como https://api.exemplo.com/api/v1.'];
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            $errors[] = 'A URL deve usar http ou https.';
        }

        $isProd = Config::isProduction();

        if ($isProd && $scheme !== 'https') {
            $errors[] = 'Em produção, a URL da API deve usar HTTPS.';
        }

        if ($isProd && self::isPrivateHost($parts['host'])) {
            $errors[] = 'Em produção, não é permitido apontar a API para endereços locais ou de rede privada.';
        }

        return $errors;
    }

    /**
     * Verifica se um host aponta para loopback, link-local ou rede privada.
     */
    public static function isPrivateHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]')); // remove colchetes de IPv6

        // Nomes locais comuns.
        if (in_array($host, ['localhost', 'localhost.localdomain', '::1'], true)) {
            return true;
        }
        if (substr($host, -6) === '.local' || substr($host, -10) === '.localhost') {
            return true;
        }

        // Se for um IP literal, avaliamos as faixas reservadas/privadas.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPrivateIp($host);
        }

        // Nome DNS: resolvemos para checar se cai em faixa privada (best-effort).
        $resolved = @gethostbynamel($host);
        if (is_array($resolved)) {
            foreach ($resolved as $ip) {
                if (self::isPrivateIp($ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function isPrivateIp(string $ip): bool
    {
        // Bloqueia privados e reservados (inclui 127.0.0.0/8, 10/8, 172.16/12,
        // 192.168/16, 169.254/16 link-local, etc.).
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Normaliza a base URL removendo barra final para concatenação previsível.
     */
    public static function normalizeBaseUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }
}
