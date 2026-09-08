<?php

namespace App\Services\Commerce;

/**
 * Rótulos amigáveis (pt-BR) para os estados de pedido/pagamento/fulfillment.
 * Usado nas views do jogador e do admin. Não contém lógica de negócio.
 */
class OrderPresenter
{
    public const ORDER_LABELS = [
        'pending'            => 'Pendente',
        'awaiting_payment'   => 'Aguardando pagamento',
        'paid'               => 'Pago',
        'cancelled'          => 'Cancelado',
        'refunded'           => 'Reembolsado',
        'partially_refunded' => 'Reembolsado parcialmente',
        'failed'             => 'Falhou',
        'expired'            => 'Expirado',
    ];

    public const PAYMENT_LABELS = [
        'none'         => 'Não iniciado',
        'pending'      => 'Aguardando',
        'in_process'   => 'Em análise',
        'approved'     => 'Aprovado',
        'rejected'     => 'Recusado',
        'cancelled'    => 'Cancelado',
        'refunded'     => 'Reembolsado',
        'charged_back' => 'Chargeback',
    ];

    public const FULFILLMENT_LABELS = [
        'none'       => 'Não aplicável',
        'pending'    => 'Entrega pendente',
        'processing' => 'Processando entrega',
        'fulfilled'  => 'Entregue',
        'failed'     => 'Falha na entrega',
        'revoked'    => 'Revogado',
    ];

    /** Classe de badge (CSS existente: success/warning/danger/muted). */
    public const BADGE_CLASS = [
        'paid' => 'success', 'approved' => 'success', 'fulfilled' => 'success',
        'pending' => 'warning', 'awaiting_payment' => 'warning', 'in_process' => 'warning',
        'processing' => 'warning',
        'failed' => 'danger', 'rejected' => 'danger', 'cancelled' => 'danger',
        'charged_back' => 'danger', 'refunded' => 'danger', 'revoked' => 'danger',
        'partially_refunded' => 'warning', 'expired' => 'muted', 'none' => 'muted',
    ];

    public static function orderLabel(string $status): string
    {
        return self::ORDER_LABELS[$status] ?? $status;
    }

    public static function paymentLabel(string $status): string
    {
        return self::PAYMENT_LABELS[$status] ?? $status;
    }

    public static function fulfillmentLabel(string $status): string
    {
        return self::FULFILLMENT_LABELS[$status] ?? $status;
    }

    public static function badge(string $status): string
    {
        return self::BADGE_CLASS[$status] ?? 'muted';
    }

    public static function money(int $cents, string $currency = 'BRL'): string
    {
        return $currency . ' ' . number_format($cents / 100, 2, ',', '.');
    }
}
