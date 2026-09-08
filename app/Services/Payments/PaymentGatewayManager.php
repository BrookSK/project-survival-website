<?php

namespace App\Services\Payments;

use App\Services\GameApi\HttpClient;

/**
 * Fábrica do gateway ativo, a partir da configuração administrável.
 *
 * Se a loja estiver desativada, o gateway for 'null' ou faltar credencial,
 * devolve NullGateway (recusa segura). Injetável para testes (MockGateway ou
 * HttpClient mockado).
 */
class PaymentGatewayManager
{
    /**
     * Resolve o gateway ativo conforme os settings.
     *
     * @param HttpClient|null $http HttpClient injetável (testes).
     */
    public static function resolve(?HttpClient $http = null): PaymentGatewayInterface
    {
        if (!PaymentConfig::enabled()) {
            return new NullGateway();
        }

        return match (PaymentConfig::gateway()) {
            'mercadopago' => new MercadoPagoGateway(
                PaymentConfig::mercadoPagoAccessToken(),
                PaymentConfig::mercadoPagoWebhookSecret(),
                PaymentConfig::currency(),
                $http
            ),
            default => new NullGateway(),
        };
    }
}
