<?php
/**
 * @var array $order
 * @var array $items
 * @var array $transactions
 * @var array $fulfillments
 * @var array $timeline
 * @var bool $canRefund
 */
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Pedido <?= e($order['reference']) ?></h1>
        <p class="page-subtitle">Jogador: <?= e($order['player_id']) ?></p>
    </div>
</div>

<div class="card">
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
        <span class="badge badge-<?= e(OrderPresenter::badge($order['order_status'])) ?>">Pedido: <?= e(OrderPresenter::orderLabel($order['order_status'])) ?></span>
        <span class="badge badge-<?= e(OrderPresenter::badge($order['payment_status'])) ?>">Pagamento: <?= e(OrderPresenter::paymentLabel($order['payment_status'])) ?></span>
        <span class="badge badge-<?= e(OrderPresenter::badge($order['fulfillment_status'])) ?>">Entrega: <?= e(OrderPresenter::fulfillmentLabel($order['fulfillment_status'])) ?></span>
    </div>

    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Item</th><th>SKU</th><th>Qtd</th><th>Unitário</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= e($it['name']) ?> <span class="muted">(<?= e($it['product_id']) ?>)</span></td>
                        <td><?= e($it['sku'] ?? '—') ?></td>
                        <td><?= (int) $it['quantity'] ?></td>
                        <td><?= e(OrderPresenter::money((int) $it['unit_price_cents'], $it['currency'])) ?></td>
                        <td><?= e(OrderPresenter::money((int) $it['line_total_cents'], $it['currency'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="mt-2"><strong>Total:</strong> <?= e(OrderPresenter::money((int) $order['total_cents'], $order['currency'])) ?>
        <?php if ((int) $order['discount_cents'] > 0): ?> · Desconto: <?= e(OrderPresenter::money((int) $order['discount_cents'], $order['currency'])) ?> (<?= e($order['coupon_code'] ?? '') ?>)<?php endif; ?>
    </p>
</div>

<div class="card">
    <h2 class="mb-2">Transações</h2>
    <?php if (empty($transactions)): ?>
        <div class="empty-state"><p>Nenhuma transação.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Gateway</th><th>ID externo</th><th>Método</th><th>Status</th><th>Valor</th><th>Estornado</th></tr></thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= e($t['gateway']) ?></td>
                            <td><code><?= e($t['external_id'] ?? '—') ?></code></td>
                            <td><?= e($t['method'] ?? '—') ?></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($t['status'])) ?>"><?= e(OrderPresenter::paymentLabel($t['status'])) ?></span></td>
                            <td><?= e(OrderPresenter::money((int) $t['amount_cents'], $t['currency'])) ?></td>
                            <td><?= e(OrderPresenter::money((int) $t['refunded_cents'], $t['currency'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="mb-2">Fulfillments</h2>
    <?php if (empty($fulfillments)): ?>
        <div class="empty-state"><p>Nenhum fulfillment.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Produto</th><th>Status</th><th>Tentativas</th><th>Último erro</th></tr></thead>
                <tbody>
                    <?php foreach ($fulfillments as $f): ?>
                        <tr>
                            <td><?= e($f['product_id']) ?></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($f['status'])) ?>"><?= e(OrderPresenter::fulfillmentLabel($f['status'])) ?></span></td>
                            <td><?= (int) $f['attempts'] ?>/<?= (int) $f['max_attempts'] ?></td>
                            <td class="muted"><?= e($f['last_error'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="mb-2">Ações</h2>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <?php if ($order['payment_status'] === 'approved' && $order['fulfillment_status'] !== 'fulfilled'): ?>
            <form method="post" action="/admin/loja/pedidos/<?= (int) $order['id'] ?>/reprocessar">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm" data-confirm="Reprocessar a entrega deste pedido junto à API do jogo?" data-form>Reprocessar entrega</button>
            </form>
        <?php endif; ?>

        <?php if ($canRefund && in_array($order['payment_status'], ['approved', 'refunded'], true)): ?>
            <form method="post" action="/admin/loja/pedidos/<?= (int) $order['id'] ?>/estornar" style="display:flex;gap:.4rem;align-items:center;">
                <?= csrf_field() ?>
                <select name="policy" class="btn btn-secondary btn-sm">
                    <option value="revoke">Estornar e revogar itens</option>
                    <option value="keep">Estornar e manter itens</option>
                </select>
                <button type="submit" class="btn btn-danger btn-sm" data-confirm="Confirmar estorno no gateway? Esta ação é registrada em auditoria." data-form>Estornar</button>
            </form>
        <?php endif; ?>
    </div>
    <p class="muted mt-2" style="font-size:.85rem;">
        O estorno é executado no gateway; a revogação de itens é solicitada à API do jogo (autoridade).
        O site nunca concede nem remove itens diretamente.
    </p>
</div>

<div class="card">
    <h2 class="mb-2">Histórico</h2>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Data</th><th>Evento</th><th>Ator</th><th>Mensagem</th></tr></thead>
            <tbody>
                <?php foreach ($timeline as $ev): ?>
                    <tr>
                        <td><?= e(format_date($ev['created_at'], 'd/m/Y H:i')) ?></td>
                        <td><code><?= e($ev['type']) ?></code></td>
                        <td class="muted"><?= e($ev['actor'] ?? '—') ?></td>
                        <td><?= e($ev['message'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
