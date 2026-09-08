<?php

namespace App\Services\GameApi;

use App\Services\SettingsService;

/**
 * Configuração central da integração com a API do jogo.
 *
 * Lê os valores administráveis (tabela `settings`, grupos `game_api` e
 * `game_api_cache`) e é a única fábrica do GameApiClient — assim os Services
 * não precisam conhecer chaves de configuração nem montar o client à mão.
 *
 * Não guarda segredos: apenas base URL, client_id público, timeout e TTLs.
 * A validação de SSRF/HTTPS fica no UrlGuard, aplicada no painel ao salvar.
 */
class GameApiConfig
{
    public const DEFAULT_BASE_URL = 'http://localhost:4000/api/v1';

    public static function isEnabled(): bool
    {
        return (bool) SettingsService::get('game_api_enabled', false);
    }

    public static function baseUrl(): string
    {
        $url = (string) SettingsService::get('game_api_base_url', self::DEFAULT_BASE_URL);
        return UrlGuard::normalizeBaseUrl($url);
    }

    public static function clientId(): string
    {
        return (string) SettingsService::get('game_api_client_id', '');
    }

    public static function timeout(): int
    {
        $t = (int) SettingsService::get('game_api_timeout', 8);
        // Faixa segura para evitar valores absurdos.
        return max(1, min($t, 30));
    }

    public static function cacheEnabled(): bool
    {
        return (bool) SettingsService::get('game_api_cache_enabled', true);
    }

    /**
     * Versão da API informada no painel (apenas rótulo/observabilidade; a
     * autoridade real é o header `X-API-Version` da resposta).
     */
    public static function apiVersion(): string
    {
        return (string) SettingsService::get('game_api_version', 'v1');
    }

    /**
     * Modo de manutenção da integração (desliga a experiência dependente da
     * API sem apagar a configuração). Editorial/administrável.
     */
    public static function maintenance(): bool
    {
        return (bool) SettingsService::get('game_api_maintenance', false);
    }

    /**
     * Canal público de release usado pelo site (stable por padrão).
     * beta/dev nunca são promovidos publicamente por padrão.
     */
    public static function releaseChannel(): string
    {
        $c = strtolower(trim((string) SettingsService::get('release_channel', 'stable')));
        return in_array($c, ['stable', 'beta', 'dev'], true) ? $c : 'stable';
    }

    /**
     * TTL (segundos) do cache de release. 0 = sem expiração (usa fallback stale).
     */
    public static function releaseCacheTtl(): int
    {
        $ttl = (int) SettingsService::get('release_cache_ttl', 900);
        return max(0, min($ttl, 86400));
    }

    /**
     * TTL (segundos) de um domínio de cache: news|events|store|categories|config.
     */
    public static function cacheTtl(string $kind): int
    {
        $map = [
            'news'       => 'game_api_cache_ttl_news',
            'events'     => 'game_api_cache_ttl_events',
            'store'      => 'game_api_cache_ttl_store',
            'categories' => 'game_api_cache_ttl_categories',
            'config'     => 'game_api_cache_ttl_config',
        ];
        $default = ['news' => 300, 'events' => 300, 'store' => 300, 'categories' => 600, 'config' => 600];
        $key = $map[$kind] ?? null;
        if ($key === null) {
            return 300;
        }
        $ttl = (int) SettingsService::get($key, $default[$kind] ?? 300);
        // Evita TTLs negativos ou exagerados.
        return max(0, min($ttl, 86400));
    }

    /**
     * Cria um GameApiClient a partir da configuração atual.
     *
     * @param HttpClient|null $http Injetável (usado em testes/mock).
     */
    public static function client(?HttpClient $http = null): GameApiClient
    {
        return new GameApiClient(
            self::baseUrl(),
            self::clientId(),
            self::timeout(),
            self::isEnabled(),
            $http
        );
    }
}
