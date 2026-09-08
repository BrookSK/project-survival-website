<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentTransaction;
use App\Models\WebhookEvent;
use App\Services\AuditService;
use App\Services\Commerce\GameFulfillmentService;
use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;

/**
 * Painel comercial (Loja) no admin.
 *
 * Segurança/integridade:
 *   - Cada ação verifica a permissão específica (store.*). Refund exige
 *     store.refunds (não concedido por padrão).
 *   - NUNCA concede/revoga itens localmente: chama a Game API (adapter).
 *   - Refund NUNCA só muda status local: chama o gateway.
 *   - Toda ação sensível registra auditoria (usuário/ação/pedido).
 *   - IDs externos são exibidos; segredos, nunca.
 */
class StoreController extends Controller
{
    private Order $orders;

    public function __construct()
    {
        $this->orders = new Order();
    }

    /**
     * Dashboard comercial: contadores e saúde da integração.
     */
    public function dashboard(Request $request): void
    {
        $this->authorize('store.view');

        $fulfillments = new Fulfillment();
        $since = date('Y-m-d H:i:s', strtotime('-30 days'));

        $this->viewAdmin('admin.store.dashboard', [
            'title'       => 'Loja',
            'breadcrumbs' => [['label' => 'Loja']],
            'metrics'     => [
                'orders_paid'        => $this->orders->countByOrderStatus('paid'),
                'orders_pending'     => $this->orders->countByOrderStatus('pending')
                                        + $this->orders->countByOrderStatus('awaiting_payment'),
                'orders_refunded'    => $this->orders->countByOrderStatus('refunded'),
                'fulfill_pending'    => $fulfillments->countByStatus('pending') + $fulfillments->countByStatus('processing'),
                'fulfill_failed'     => $fulfillments->countByStatus('failed'),
                'revenue_30d_cents'  => $this->orders->approvedTotalSince($since),
                'reconcile_count'    => count($this->orders->paidWithoutFulfillment(500)),
            ],
            'health'      => [
                'enabled'     => PaymentConfig::enabled(),
                'gateway'     => PaymentConfig::gateway(),
                'environment' => PaymentConfig::environment(),
                'game_service'=> PaymentConfig::gameServiceClientId() !== '',
            ],
        ]);
    }

    /**
     * Lista de pedidos com filtros.
     */
    public function orders(Request $request): void
    {
        $this->authorize('store.orders');

        $page = (int) $request->query('page', 1);
        $filters = [
            'order_status'   => (string) $request->query('order_status', ''),
            'payment_status' => (string) $request->query('payment_status', ''),
            'search'         => trim((string) $request->query('q', '')),
        ];
        $result = $this->orders->paginate($page, 20, $filters);

        $this->viewAdmin('admin.store.orders.index', [
            'title'       => 'Pedidos',
            'breadcrumbs' => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Pedidos']],
            'result'      => $result,
            'filters'     => $filters,
        ]);
    }

    /**
     * Detalhe do pedido (timeline, transações, fulfillments).
     */
    public function orderShow(Request $request, array $params): void
    {
        $this->authorize('store.orders');

        $order = $this->orders->find((int) ($params['id'] ?? 0));
        if (!$order) {
            $this->abort(404);
            return;
        }

        $this->viewAdmin('admin.store.orders.show', [
            'title'        => 'Pedido ' . $order['reference'],
            'breadcrumbs'  => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Pedidos', 'url' => '/admin/loja/pedidos'], ['label' => $order['reference']]],
            'order'        => $order,
            'items'        => $this->orders->items((int) $order['id']),
            'transactions' => (new PaymentTransaction())->forOrder((int) $order['id']),
            'fulfillments' => (new Fulfillment())->forOrder((int) $order['id']),
            'timeline'     => (new OrderEvent())->forOrder((int) $order['id']),
            'canRefund'    => \App\Services\AuthService::can('store.refunds'),
        ]);
    }

    /**
     * Lista de transações de pagamento.
     */
    public function transactions(Request $request): void
    {
        $this->authorize('store.payments');
        $page = (int) $request->query('page', 1);
        $result = (new PaymentTransaction())->paginate($page, 20, ['status' => (string) $request->query('status', '')]);

        $this->viewAdmin('admin.store.transactions', [
            'title'       => 'Transações',
            'breadcrumbs' => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Transações']],
            'result'      => $result,
        ]);
    }

    /**
     * Log de webhooks recebidos.
     */
    public function webhooks(Request $request): void
    {
        $this->authorize('store.webhooks');
        $page = (int) $request->query('page', 1);
        $result = (new WebhookEvent())->paginate($page, 20, ['status' => (string) $request->query('status', '')]);

        $this->viewAdmin('admin.store.webhooks', [
            'title'       => 'Webhooks',
            'breadcrumbs' => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Webhooks']],
            'result'      => $result,
        ]);
    }

    /**
     * Reconciliação: pedidos pagos sem fulfillment concluído.
     */
    public function reconciliation(Request $request): void
    {
        $this->authorize('store.reconciliation');
        $orders = $this->orders->paidWithoutFulfillment(200);

        $this->viewAdmin('admin.store.reconciliation', [
            'title'       => 'Reconciliação',
            'breadcrumbs' => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Reconciliação']],
            'orders'      => $orders,
        ]);
    }

    /**
     * Fulfillments pendentes/prontos para retry.
     */
    public function fulfillments(Request $request): void
    {
        $this->authorize('store.fulfillment');
        $due = (new Fulfillment())->dueForRetry(200);

        $this->viewAdmin('admin.store.fulfillments', [
            'title'       => 'Fulfillments',
            'breadcrumbs' => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Fulfillments']],
            'due'         => $due,
        ]);
    }

    /**
     * Reprocessa (retry manual) o fulfillment de um pedido. Chama a Game API
     * (idempotente) — nunca concede localmente.
     */
    public function reprocessFulfillment(Request $request, array $params): void
    {
        $this->authorize('store.fulfillment');
        $this->verifyCsrf($request);

        $order = $this->orders->find((int) ($params['id'] ?? 0));
        if (!$order) {
            $this->abort(404);
            return;
        }
        if ($order['payment_status'] !== 'approved') {
            Session::flash('error', 'Só é possível processar entrega de pedidos com pagamento aprovado.');
            $this->redirect('/admin/loja/pedidos/' . $order['id']);
            return;
        }

        $items = $this->orders->items((int) $order['id']);
        $tx = (new PaymentTransaction())->forOrder((int) $order['id']);
        $latest = $tx ? end($tx) : null;
        $order['payment_reference'] = $latest['external_id'] ?? '';

        (new GameFulfillmentService())->fulfillOrder($order, $items);
        AuditService::log('reprocess', 'store_fulfillment', $order['reference'], 'Reprocessou fulfillment');

        Session::flash('success', 'Reprocessamento solicitado à API do jogo.');
        $this->redirect('/admin/loja/pedidos/' . $order['id']);
    }

    /**
     * Estorna um pagamento (exige store.refunds). Chama o gateway e, conforme a
     * política, solicita revogação de itens à Game API. NUNCA só muda status.
     */
    public function refund(Request $request, array $params): void
    {
        $this->authorize('store.refunds');
        $this->verifyCsrf($request);

        $order = $this->orders->find((int) ($params['id'] ?? 0));
        if (!$order) {
            $this->abort(404);
            return;
        }
        if (!in_array($order['payment_status'], ['approved', 'refunded'], true)) {
            Session::flash('error', 'Só é possível estornar pagamentos aprovados.');
            $this->redirect('/admin/loja/pedidos/' . $order['id']);
            return;
        }

        $tx = (new PaymentTransaction())->forOrder((int) $order['id']);
        $latest = $tx ? end($tx) : null;
        if (!$latest || empty($latest['external_id'])) {
            Session::flash('error', 'Nenhuma transação estornável encontrada.');
            $this->redirect('/admin/loja/pedidos/' . $order['id']);
            return;
        }

        $policy = $request->post('policy') === 'keep' ? 'keep' : PaymentConfig::refundDefaultPolicy();

        try {
            (new PaymentService())->refund((int) $order['id'], (string) $latest['external_id']);
        } catch (PaymentException $e) {
            Session::flash('error', 'Falha ao estornar no gateway: ' . $e->getMessage());
            $this->redirect('/admin/loja/pedidos/' . $order['id']);
            return;
        }

        // Atualiza o pedido e solicita revogação (política revoke) à Game API.
        $this->orders->updateStates((int) $order['id'], ['order_status' => 'refunded', 'payment_status' => 'refunded']);
        $items = $this->orders->items((int) $order['id']);
        $order['payment_reference'] = $latest['external_id'];
        (new GameFulfillmentService())->revokeOrder($order, $items, $policy, 'refund');

        AuditService::log('refund', 'store_order', $order['reference'], "Estornou pagamento (política: {$policy})");
        Session::flash('success', 'Estorno solicitado ao gateway.');
        $this->redirect('/admin/loja/pedidos/' . $order['id']);
    }
}
