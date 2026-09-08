<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pedido comercial. Guarda snapshot de totais em centavos e três estados
 * INDEPENDENTES: order_status, payment_status, fulfillment_status.
 *
 * Entitlement/inventário NÃO vivem aqui — são autoridade da Game API. Este
 * model registra apenas o pedido e seus resultados financeiros/de entrega.
 */
class Order extends Model
{
    protected string $table = 'orders';
    protected array $fillable = [
        'reference', 'player_id', 'player_email',
        'order_status', 'payment_status', 'fulfillment_status',
        'currency', 'subtotal_cents', 'discount_cents', 'total_cents',
        'coupon_id', 'coupon_code', 'gateway',
        'terms_version', 'refund_terms_version',
        'idempotency_key', 'client_ip', 'user_agent',
        'paid_at', 'expires_at',
    ];

    public const ORDER_STATUSES = ['pending', 'awaiting_payment', 'paid', 'cancelled', 'refunded', 'partially_refunded', 'failed', 'expired'];
    public const PAYMENT_STATUSES = ['none', 'pending', 'in_process', 'approved', 'rejected', 'cancelled', 'refunded', 'charged_back'];
    public const FULFILLMENT_STATUSES = ['none', 'pending', 'processing', 'fulfilled', 'failed', 'revoked'];

    public function findByReference(string $reference): ?array
    {
        return $this->findBy('reference', $reference);
    }

    public function findByIdempotencyKey(string $key): ?array
    {
        return $this->findBy('idempotency_key', $key);
    }

    /**
     * Gera uma referência pública única (ex.: SITE-000123) a partir do id.
     */
    public static function referenceFor(int $id): string
    {
        return 'SITE-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Itens de um pedido.
     */
    public function items(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `order_items` WHERE `order_id` = :id ORDER BY `id` ASC",
            ['id' => $orderId]
        );
    }

    /**
     * Atualiza os três estados (apenas os informados) de forma atômica.
     *
     * @param array{order_status?:string,payment_status?:string,fulfillment_status?:string,paid_at?:?string} $states
     */
    public function updateStates(int $orderId, array $states): int
    {
        $sets = [];
        $params = ['id' => $orderId];
        foreach (['order_status', 'payment_status', 'fulfillment_status'] as $col) {
            if (isset($states[$col])) {
                $sets[] = "`{$col}` = :{$col}";
                $params[$col] = $states[$col];
            }
        }
        if (array_key_exists('paid_at', $states)) {
            $sets[] = "`paid_at` = :paid_at";
            $params['paid_at'] = $states['paid_at'];
        }
        if (!$sets) {
            return 0;
        }
        return $this->db->execute(
            "UPDATE `orders` SET " . implode(', ', $sets) . " WHERE `id` = :id",
            $params
        );
    }

    /**
     * Lista paginada com filtros (status, player, gateway, busca por referência).
     */
    public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if (!empty($filters['order_status']) && in_array($filters['order_status'], self::ORDER_STATUSES, true)) {
            $where[] = '`order_status` = :os';
            $params['os'] = $filters['order_status'];
        }
        if (!empty($filters['payment_status']) && in_array($filters['payment_status'], self::PAYMENT_STATUSES, true)) {
            $where[] = '`payment_status` = :ps';
            $params['ps'] = $filters['payment_status'];
        }
        if (!empty($filters['fulfillment_status']) && in_array($filters['fulfillment_status'], self::FULFILLMENT_STATUSES, true)) {
            $where[] = '`fulfillment_status` = :fs';
            $params['fs'] = $filters['fulfillment_status'];
        }
        if (!empty($filters['player_id'])) {
            $where[] = '`player_id` = :pid';
            $params['pid'] = $filters['player_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(`reference` LIKE :q OR `player_email` LIKE :q)';
            $params['q'] = '%' . $filters['search'] . '%';
        }
        $clause = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM `orders`{$clause}", $params);
        $rows = $this->db->fetchAll(
            "SELECT * FROM `orders`{$clause} ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Pedidos de um jogador (para a área da conta). Somente os campos exibíveis.
     */
    public function forPlayer(string $playerId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db->fetchAll(
            "SELECT `id`, `reference`, `order_status`, `payment_status`, `fulfillment_status`,
                    `currency`, `total_cents`, `created_at`, `paid_at`
             FROM `orders` WHERE `player_id` = :pid ORDER BY `created_at` DESC LIMIT {$limit}",
            ['pid' => $playerId]
        );
    }

    /**
     * Pedido pago cujo fulfillment ainda não concluiu (reconciliação).
     */
    public function paidWithoutFulfillment(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            "SELECT * FROM `orders`
             WHERE `payment_status` = 'approved'
               AND `fulfillment_status` IN ('none','pending','processing','failed')
             ORDER BY `paid_at` ASC LIMIT {$limit}"
        );
    }

    public function countByOrderStatus(string $status): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM `orders` WHERE `order_status` = :s",
            ['s' => $status]
        );
    }

    /**
     * Soma de vendas aprovadas (centavos) desde uma data.
     */
    public function approvedTotalSince(string $since): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(`total_cents`),0) FROM `orders`
             WHERE `payment_status` = 'approved' AND `paid_at` >= :since",
            ['since' => $since]
        );
    }
}
