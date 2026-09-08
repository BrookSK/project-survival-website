<?php

use App\Services\Payments\MercadoPagoGateway;

/**
 * Validação de assinatura de webhook do Mercado Pago (HMAC-SHA256).
 * Não toca em rede: parseWebhook só valida assinatura e extrai IDs.
 */
class MercadoPagoWebhookTest extends TestCase
{
    private const SECRET = 'segredo_webhook_teste';

    private function gateway(): MercadoPagoGateway
    {
        // Access token não vazio (para isEnabled), webhook secret conhecido.
        return new MercadoPagoGateway('APP_USR-token-teste', self::SECRET, 'BRL', new MockHttpClient());
    }

    /**
     * Gera um header x-signature válido para um dado data.id + request-id + ts.
     */
    private function signedHeaders(string $dataId, string $requestId, string $ts): array
    {
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, self::SECRET);
        return [
            'x-signature'  => "ts={$ts},v1={$hash}",
            'x-request-id' => $requestId,
        ];
    }

    public function testValidSignatureIsAccepted(): void
    {
        $dataId = 'mp_123';
        $headers = $this->signedHeaders($dataId, 'req-abc', '1700000000');
        $body = json_encode(['id' => 'evt_1', 'type' => 'payment', 'data' => ['id' => $dataId]]);

        $result = $this->gateway()->parseWebhook($headers, $body, []);

        $this->assertTrue($result->signatureValid, 'assinatura correta deveria ser válida');
        $this->assertEquals('mp_123', $result->externalPaymentId);
        $this->assertTrue($result->isActionable());
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $dataId = 'mp_123';
        $headers = $this->signedHeaders($dataId, 'req-abc', '1700000000');
        $headers['x-signature'] = 'ts=1700000000,v1=deadbeef'; // hash inválido
        $body = json_encode(['id' => 'evt_1', 'data' => ['id' => $dataId]]);

        $result = $this->gateway()->parseWebhook($headers, $body, []);

        $this->assertFalse($result->signatureValid, 'assinatura adulterada deve ser rejeitada');
    }

    public function testMissingSignatureIsRejected(): void
    {
        $body = json_encode(['id' => 'evt_1', 'data' => ['id' => 'mp_123']]);
        $result = $this->gateway()->parseWebhook([], $body, []);
        $this->assertFalse($result->signatureValid);
    }

    public function testSignatureForDifferentPaymentIsRejected(): void
    {
        // Assinatura gerada para outro data.id não deve validar o payload atual.
        $headers = $this->signedHeaders('mp_OUTRO', 'req-abc', '1700000000');
        $body = json_encode(['id' => 'evt_1', 'data' => ['id' => 'mp_123']]);
        $result = $this->gateway()->parseWebhook($headers, $body, []);
        $this->assertFalse($result->signatureValid);
    }
}
