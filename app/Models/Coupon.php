<?php

namespace App\Models;

use App\Core\Model;

/**
 * Cupom de desconto. A validação é SEMPRE feita no backend (nunca confiar no
 * navegador). Desconto em percentual (0-100) ou valor fixo (centavos).
 */
class Coupon extends Model
{
    protected string $table = 'coupons';
    protected array $fillable = [
        'code', 'description', 'type', 'percent_off', 'amount_off_cents',
        'currency', 'min_total_cents', 'max_redemptions', 'redeemed_count',
        'per_player_limit', 'active', 'starts_at', 'ends_at', 'created_by',
    ];

    public const TYPES = ['percent', 'fixed'];

    public function findByCode(string $code): ?array
    {
        return $this->findBy('code', strtoupper(trim($code)));
    }

    /**
     * Valida um cupom para um pedido. Retorna:
     *   ['valid'=>bool, 'error'=>?string, 'coupon'=>?array, 'discount_cents'=>int]
     *
     * A validação considera: existência, ativo, janela de vigência, moeda,
     * total mínimo, limite global e limite por jogador. O desconto é calculado
     * no backend e nunca excede o subtotal.
     */
    public function validateFor(string $code, int $subtotalCents, string $currency, string $playerId): array
    {
        $fail = static fn (string $msg) => ['valid' => false, 'error' => $msg, 'coupon' => null, 'discount_cents' => 0];

        $coupon = $this->findByCode($code);
        if (!$coupon) {
            return $fail('Cupom inválido.');
        }
        if ((int) $coupon['active'] !== 1) {
            return $fail('Cupom inativo.');
        }
        $now = date('Y-m-d H:i:s');
        if (!empty($coupon['starts_at']) && $coupon['starts_at'] > $now) {
            return $fail('Cupom ainda não está válido.');
        }
        if (!empty($coupon['ends_at']) && $coupon['ends_at'] < $now) {
            return $fail('Cupom expirado.');
        }
        if (strtoupper($coupon['currency']) !== strtoupper($currency)) {
            return $fail('Cupom não é válido para esta moeda.');
        }
        if ($subtotalCents < (int) $coupon['min_total_cents']) {
            return $fail('Pedido não atinge o mínimo para este cupom.');
        }
        if ($coupon['max_redemptions'] !== null && (int) $coupon['redeemed_count'] >= (int) $coupon['max_redemptions']) {
            return $fail('Cupom esgotado.');
        }
        if ($coupon['per_player_limit'] !== null) {
            $used = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM `coupon_redemptions` WHERE `coupon_id` = :cid AND `player_id` = :pid",
                ['cid' => $coupon['id'], 'pid' => $playerId]
            );
            if ($used >= (int) $coupon['per_player_limit']) {
                return $fail('Você já utilizou este cupom o número máximo de vezes.');
            }
        }

        $discount = $this->computeDiscount($coupon, $subtotalCents);
        if ($discount <= 0) {
            return $fail('Cupom não gera desconto para este pedido.');
        }

        return ['valid' => true, 'error' => null, 'coupon' => $coupon, 'discount_cents' => $discount];
    }

    /**
     * Calcula o desconto em centavos, limitado ao subtotal.
     */
    public function computeDiscount(array $coupon, int $subtotalCents): int
    {
        if ($coupon['type'] === 'fixed') {
            $discount = (int) $coupon['amount_off_cents'];
        } else {
            $percent = (float) $coupon['percent_off'];
            // Arredonda para o centavo mais próximo (metade para cima).
            $discount = (int) round($subtotalCents * $percent / 100);
        }
        return max(0, min($discount, $subtotalCents));
    }

    /**
     * Registra o uso do cupom (uma vez por pedido) e incrementa a contagem.
     * Chamado dentro da transação do pedido.
     */
    public function redeem(int $couponId, int $orderId, string $playerId, int $discountCents): void
    {
        $this->db->execute(
            "INSERT IGNORE INTO `coupon_redemptions` (`coupon_id`, `order_id`, `player_id`, `discount_cents`)
             VALUES (:cid, :oid, :pid, :disc)",
            ['cid' => $couponId, 'oid' => $orderId, 'pid' => $playerId, 'disc' => $discountCents]
        );
        $this->db->execute(
            "UPDATE `coupons` SET `redeemed_count` = `redeemed_count` + 1 WHERE `id` = :id",
            ['id' => $couponId]
        );
    }

    /**
     * Libera o resgate (ex.: pedido cancelado antes do pagamento).
     */
    public function releaseRedemption(int $orderId): void
    {
        $row = $this->db->fetch(
            "SELECT `coupon_id` FROM `coupon_redemptions` WHERE `order_id` = :oid LIMIT 1",
            ['oid' => $orderId]
        );
        if (!$row) {
            return;
        }
        $this->db->execute("DELETE FROM `coupon_redemptions` WHERE `order_id` = :oid", ['oid' => $orderId]);
        $this->db->execute(
            "UPDATE `coupons` SET `redeemed_count` = GREATEST(`redeemed_count` - 1, 0) WHERE `id` = :id",
            ['id' => $row['coupon_id']]
        );
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `coupons`");
        $rows = $this->db->fetchAll(
            "SELECT * FROM `coupons` ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }
}
