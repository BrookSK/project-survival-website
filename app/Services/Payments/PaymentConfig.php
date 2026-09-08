<?php

namespace App\Services\Payments;

use App\Services\SettingsService;

/**
 * Configuração da camada comercial (grupo de settings `payments`).
 *
 * Sem `.env`: tudo vem do painel. Segredos (access token, webhook secret,
 * service secret) são lidos aqui mas NUNCA logados nem devolvidos ao navegador.
 */
class PaymentConfig
{
    public static function enabled(): bool
    {
        return (bool) SettingsService::get('payments_enabled', false);
    }

    public static function gateway(): string
    {
        $g = strtolower(trim((string) SettingsService::get('payment_gateway', 'null')));
        return $g !== '' ? $g : 'null';
    }

    public static function environment(): string
    {
        $env = strtolower(trim((string) SettingsService::get('payment_environment', 'sandbox')));
        return $env === 'production' ? 'production' : 'sandbox';
    }

    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    public static function currency(): string
    {
        $c = strtoupper(trim((string) SettingsService::get('payment_currency', 'BRL')));
        return $c !== '' ? substr($c, 0, 3) : 'BRL';
    }

    // --- Mercado Pago ---

    public static function mercadoPagoPublicKey(): string
    {
        return (string) SettingsService::get('mercadopago_public_key', '');
    }

    public static function mercadoPagoAccessToken(): string
    {
        return (string) SettingsService::get('mercadopago_access_token', '');
    }

    public static function mercadoPagoWebhookSecret(): string
    {
        return (string) SettingsService::get('mercadopago_webhook_secret', '');
    }

    // --- Service account da Game API (concessão/estorno server-to-server) ---

    public static function gameServiceClientId(): string
    {
        return (string) SettingsService::get('game_api_service_client_id', '');
    }

    public static function gameServiceSecret(): string
    {
        return (string) SettingsService::get('game_api_service_secret', '');
    }

    // --- Política de fulfillment/refund ---

    /**
     * Modo de fulfillment:
     *   - `game_webhook` (OFICIAL, padrão): a Game API concede via webhook do
     *     provedor de pagamento. O site NÃO concede.
     *   - `commerce_endpoint`: usa um endpoint dedicado da Game API (só se/quando
     *     existir contrato oficial). Ver docs/api/commercial-integration.md.
     */
    public static function fulfillmentMode(): string
    {
        $m = strtolower(trim((string) SettingsService::get('fulfillment_mode', 'game_webhook')));
        return $m === 'commerce_endpoint' ? 'commerce_endpoint' : 'game_webhook';
    }

    public static function fulfillmentMaxAttempts(): int
    {
        $n = (int) SettingsService::get('fulfillment_max_attempts', 8);
        return max(1, min($n, 50));
    }

    public static function refundDefaultPolicy(): string
    {
        $p = strtolower(trim((string) SettingsService::get('refund_default_policy', 'revoke')));
        return $p === 'keep' ? 'keep' : 'revoke';
    }
}
