<?php

use App\Core\Database;
use App\Models\Coupon;

/**
 * Cálculo de desconto de cupom (centavos, arredondamento, nunca > subtotal).
 * Usa StubDatabase para instanciar o Model sem conectar; computeDiscount é
 * lógica pura sobre o array do cupom.
 */
class CouponDiscountTest extends TestCase
{
    private function coupon(): Coupon
    {
        Database::setInstance(new StubDatabase());
        return new Coupon();
    }

    public function testPercentDiscountRoundsToCents(): void
    {
        $c = $this->coupon();
        // 10% de 4990 = 499.0 -> 499 centavos
        $this->assertEquals(499, $c->computeDiscount(['type' => 'percent', 'percent_off' => 10], 4990));
        // 15% de 999 = 149.85 -> 150 (arredonda)
        $this->assertEquals(150, $c->computeDiscount(['type' => 'percent', 'percent_off' => 15], 999));
    }

    public function testFixedDiscountNeverExceedsSubtotal(): void
    {
        $c = $this->coupon();
        $this->assertEquals(500, $c->computeDiscount(['type' => 'fixed', 'amount_off_cents' => 500], 4990));
        // Desconto maior que o subtotal é limitado ao subtotal.
        $this->assertEquals(4990, $c->computeDiscount(['type' => 'fixed', 'amount_off_cents' => 9999], 4990));
    }
}
