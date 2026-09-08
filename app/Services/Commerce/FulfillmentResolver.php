<?php

namespace App\Services\Commerce;

use App\Services\GameApi\HttpClient;
use App\Services\Payments\PaymentConfig;

/**
 * Resolve a implementação de fulfillment conforme a configuração.
 *
 * Padrão OFICIAL: `game_webhook` -> NullFulfillmentAdapter (a Game API concede
 * via webhook do provedor; o site não concede). O modo `commerce_endpoint`
 * (GameFulfillmentService) só deve ser usado se/quando a Game API expuser um
 * endpoint dedicado com contrato oficial.
 */
class FulfillmentResolver
{
    public static function resolve(?HttpClient $http = null): GameFulfillmentInterface
    {
        if (PaymentConfig::fulfillmentMode() === 'commerce_endpoint') {
            return new GameFulfillmentService($http);
        }
        return new NullFulfillmentAdapter();
    }
}
