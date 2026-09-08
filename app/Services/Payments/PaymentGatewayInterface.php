<?php

namespace App\Services\Payments;

/**
 * Contrato de um gateway de pagamento. Toda comunicação com o provedor é
 * server-side. Nenhuma implementação simula pagamento: se o provedor não
 * confirmar, o estado não avança.
 *
 * Valores monetários SEMPRE em centavos (inteiro).
 */
interface PaymentGatewayInterface
{
    /**
     * Identificador do gateway (ex.: 'mercadopago', 'null').
     */
    public function name(): string;

    /**
     * O gateway está habilitado e configurado para operar?
     */
    public function isEnabled(): bool;

    /**
     * Cria um pagamento no gateway a partir de um pedido já persistido.
     *
     * @param array  $order  Linha do pedido (reference, total_cents, currency, player_email...).
     * @param string $method Método desejado: 'pix' | 'credit_card'.
     * @param array  $options Dados adicionais (ex.: token de cartão gerado no navegador).
     *
     * @throws PaymentException Em falha (retryable indica se é transitória).
     */
    public function createPayment(array $order, string $method, array $options = []): PaymentIntent;

    /**
     * Consulta o estado REAL de um pagamento no gateway (fonte de verdade do
     * status financeiro). Usado após webhooks — nunca confiamos no payload.
     *
     * @throws PaymentException
     */
    public function getPayment(string $externalId): PaymentIntent;

    /**
     * Solicita o estorno (total ou parcial) de um pagamento.
     *
     * @param int|null $amountCents Valor a estornar; null = total.
     *
     * @throws PaymentException
     */
    public function refundPayment(string $externalId, ?int $amountCents = null): RefundResult;

    /**
     * Analisa um webhook recebido, validando a assinatura e extraindo o ID do
     * pagamento referenciado. NÃO decide status (isso vem de getPayment()).
     *
     * @param array  $headers Cabeçalhos da requisição (para assinatura).
     * @param string $rawBody Corpo bruto recebido.
     * @param array  $query   Parâmetros de query (alguns provedores usam).
     */
    public function parseWebhook(array $headers, string $rawBody, array $query = []): WebhookResult;
}
