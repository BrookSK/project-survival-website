<?php

use App\Services\Payments\PaymentStatus;

/**
 * Vocabulário de status de pagamento (unidade pura, sem DB/rede).
 */
class PaymentStatusTest extends TestCase
{
    public function testNormalizeMapsUnknownToPending(): void
    {
        $this->assertEquals(PaymentStatus::PENDING, PaymentStatus::normalize('coisa-estranha'));
        $this->assertEquals(PaymentStatus::APPROVED, PaymentStatus::normalize('APPROVED'));
        $this->assertEquals(PaymentStatus::REFUNDED, PaymentStatus::normalize('refunded'));
    }

    public function testOnlyApprovedIsFinalApproved(): void
    {
        $this->assertTrue(PaymentStatus::isFinalApproved(PaymentStatus::APPROVED));
        $this->assertFalse(PaymentStatus::isFinalApproved(PaymentStatus::PENDING));
        $this->assertFalse(PaymentStatus::isFinalApproved(PaymentStatus::IN_PROCESS));
    }

    public function testFailureDetection(): void
    {
        $this->assertTrue(PaymentStatus::isFailure(PaymentStatus::REJECTED));
        $this->assertTrue(PaymentStatus::isFailure(PaymentStatus::CANCELLED));
        $this->assertFalse(PaymentStatus::isFailure(PaymentStatus::APPROVED));
    }
}
