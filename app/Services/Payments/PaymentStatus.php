<?php

namespace App\Services\Payments;

/**
 * Vocabulário normalizado de status de pagamento, independente de gateway.
 * Cada gateway mapeia seus próprios status para estes.
 */
final class PaymentStatus
{
    public const PENDING     = 'pending';
    public const IN_PROCESS  = 'in_process';
    public const APPROVED    = 'approved';
    public const REJECTED    = 'rejected';
    public const CANCELLED   = 'cancelled';
    public const REFUNDED    = 'refunded';
    public const CHARGED_BACK = 'charged_back';

    public const ALL = [
        self::PENDING, self::IN_PROCESS, self::APPROVED, self::REJECTED,
        self::CANCELLED, self::REFUNDED, self::CHARGED_BACK,
    ];

    public static function isFinalApproved(string $status): bool
    {
        return $status === self::APPROVED;
    }

    public static function isFailure(string $status): bool
    {
        return in_array($status, [self::REJECTED, self::CANCELLED], true);
    }

    public static function normalize(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return in_array($status, self::ALL, true) ? $status : self::PENDING;
    }
}
