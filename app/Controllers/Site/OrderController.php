<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentTransaction;
use App\Services\GameApi\GameStoreService;
use App\Services\GameApi\PlayerSession;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaymentStatus;

/**
 * Pedidos do jogador (protegido por PlayerAuthMiddleware).
 *
 * Os estados exibidos refletem a confirmação REAL (gateway/Game API), nunca a
 * página de sucesso. O acesso a um pedido é validado por ownership (anti-IDOR):
 * o pedido precisa pertencer ao player_id da sessão.
 */
class OrderController extends Controller
{
    private Order $orders;

    public function __construct()
    {
        $this->orders = new Order();
    }

    /**
     * Lista os pedidos do jogador logado.
     */
    public function index(Request $request): void
    {
        $playerId = PlayerSession::userId();
        $orders = $playerId !== null ? $this->orders->forPlayer($playerId) : [];

        $this->viewSite('site.account.orders', [
            'title'  => 'Meus pedidos',
            'orders' => $orders,
        ]);
    }

    /**
     * Detalhe de um pedido (com timeline). Anti-IDOR por player_id da sessão.
     */
    public function show(Request $request, array $params): void
    {
        $reference = (string) ($params['reference'] ?? '');
        $playerId = PlayerSession::userId();

        $order = $reference !== '' ? $this->orders->findByReference($reference) : null;

        // Ownership: 404 (não 403) para não revelar existência de pedidos alheios.
        if (!$order || $playerId === null || (string) $order['player_id'] !== (string) $playerId) {
            $this->abort(404);
            return;
        }

        $items = $this->orders->items((int) $order['id']);
        $transactions = (new PaymentTransaction())->forOrder((int) $order['id']);
        $fulfillments = (new Fulfillment())->forOrder((int) $order['id']);
        $timeline = (new OrderEvent())->forOrder((int) $order['id']);

        // Se pago e ainda não entregue, e o pagamento tinha ficado pendente,
        // reconsulta o gateway para refletir o estado real (sem confiar na tela).
        $order = $this->maybeSync($order, $transactions);

        // Após entrega confirmada, invalida cache público de catálogo (owned muda).
        if ($order['fulfillment_status'] === 'fulfilled') {
            GameStoreService::flushCache();
        }

        $this->viewSite('site.account.order-detail', [
            'title'        => 'Pedido ' . $order['reference'],
            'order'        => $order,
            'items'        => $items,
            'transactions' => $transactions,
            'fulfillments' => $fulfillments,
            'timeline'     => $timeline,
        ]);
    }

    /**
     * Reconsulta o gateway quando o pagamento ainda não está resolvido.
     * Nunca inventa aprovação: só sincroniza com o estado real.
     */
    private function maybeSync(array $order, array $transactions): array
    {
        if (in_array($order['payment_status'], [PaymentStatus::APPROVED, PaymentStatus::REFUNDED, PaymentStatus::CHARGED_BACK], true)) {
            return $order;
        }
        $latest = $transactions ? end($transactions) : null;
        if (!$latest || empty($latest['external_id'])) {
            return $order;
        }
        try {
            (new PaymentService())->syncFromGateway((int) $order['id'], (string) $latest['external_id']);
            return $this->orders->findByReference($order['reference']) ?? $order;
        } catch (PaymentException $e) {
            return $order;
        }
    }
}
