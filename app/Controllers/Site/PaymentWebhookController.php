<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Services\Commerce\WebhookProcessor;
use App\Services\Payments\PaymentConfig;

/**
 * Recebe webhooks do gateway de pagamento.
 *
 * Esta rota fica FORA de autenticação e de CSRF (quem chama é o gateway,
 * server-to-server). A segurança vem da VALIDAÇÃO DE ASSINATURA feita pelo
 * gateway (WebhookProcessor -> PaymentService -> gateway->parseWebhook).
 *
 * Regras:
 *   - NUNCA confia no payload como prova; consulta o gateway para o estado real.
 *   - Idempotente (webhook_events.event_id UNIQUE).
 *   - Responde rápido. Assinatura inválida -> 401; demais -> 200 (evita
 *     reentrega infinita de eventos que já tratamos/ignoramos).
 */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, array $params): void
    {
        $provider = strtolower((string) ($params['provider'] ?? ''));

        // Loja desativada ou provedor desconhecido: aceita silenciosamente (200)
        // para não gerar reentregas, mas não processa.
        if (!PaymentConfig::enabled() || $provider === '') {
            $this->json(['received' => true], 200);
            return;
        }

        $rawBody = file_get_contents('php://input') ?: '';
        $headers = $this->collectHeaders();
        $query = $_GET;

        try {
            $result = (new WebhookProcessor())->process($provider, $headers, $rawBody, $query);
        } catch (\Throwable $e) {
            // Erro inesperado: loga sem vazar detalhes e responde 200 para o
            // gateway reenviar mais tarde apenas se for o caso dele.
            Logger::error('webhook.exception', ['provider' => $provider, 'error' => $e->getMessage()]);
            $this->json(['received' => true], 200);
            return;
        }

        if ($result === 'invalid') {
            // Assinatura inválida: sinaliza erro ao gateway.
            $this->json(['error' => 'invalid signature'], 401);
            return;
        }

        $this->json(['received' => true, 'result' => $result], 200);
    }

    /**
     * Coleta os headers da requisição a partir de $_SERVER (HTTP_*).
     */
    private function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        // x-request-id às vezes vem como cabeçalho não HTTP_ prefixado em alguns SAPIs.
        if (isset($_SERVER['X-Request-Id'])) {
            $headers['x-request-id'] = $_SERVER['X-Request-Id'];
        }
        return $headers;
    }
}
