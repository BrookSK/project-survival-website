<?php
/**
 * @var array $result
 * @var array $filters
 */
use App\Models\Order;
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Pedidos</h1>
        <p class="page-subtitle">Pedidos comerciais e seus estados de pagamento/entrega.</p>
    </div>
</div>

<div class="card">
    <form method="get" action="/admin/loja/pedidos" class="toolbar" style="border-bottom:1px solid var(--border);padding-bottom:1rem;margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
        <input type="text" name="q" value="<?= e($filters['search']) ?>" placeholder="Referência ou e-mail" class="btn btn-secondary btn-sm" style="min-width:200px;">
        <select name="payment_status" class="btn btn-secondary btn-sm">
            <option value="">Pagamento (todos)</option>
            <?php foreach (Order::PAYMENT_STATUSES as $s): ?>
                <option value="<?= e($s) ?>" <?= $filters['payment_status'] === $s ? 'selected' : '' ?>><?= e(OrderPresenter::paymentLabel($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="order_status" class="btn btn-secondary btn-sm">
            <option value="">Pedido (todos)</option>
            <?php foreach (Order::ORDER_STATUSES as $s): ?>
                <option value="<?= e($s) ?>" <?= $filters['order_status'] === $s ? 'selected' : '' ?>><?= e(OrderPresenter::orderLabel($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
    </form>

    <?php if (empty($result['data'])): ?>
        <div class="empty-state"><p>Nenhum pedido encontrado.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Referência</th><th>Jogador</th><th>Total</th><th>Pagamento</th><th>Entrega</th><th>Criado</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($result['data'] as $o): ?>
                        <tr>
                            <td><code><?= e($o['reference']) ?></code></td>
                            <td><?= e($o['player_id']) ?></td>
                            <td><?= e(OrderPresenter::money((int) $o['total_cents'], $o['currency'])) ?></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($o['payment_status'])) ?>"><?= e(OrderPresenter::paymentLabel($o['payment_status'])) ?></span></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($o['fulfillment_status'])) ?>"><?= e(OrderPresenter::fulfillmentLabel($o['fulfillment_status'])) ?></span></td>
                            <td><?= e(format_date($o['created_at'], 'd/m/Y H:i')) ?></td>
                            <td class="actions"><a href="/admin/loja/pedidos/<?= (int) $o['id'] ?>" class="btn btn-secondary btn-sm">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $result['page'], 'total' => $result['total'], 'perPage' => $result['per_page'], 'baseUrl' => '/admin/loja/pedidos', 'query' => $filters]) ?>
    <?php endif; ?>
</div>
