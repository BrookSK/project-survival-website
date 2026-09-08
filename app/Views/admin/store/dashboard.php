<?php
/**
 * @var array $metrics
 * @var array $health
 */
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Loja</h1>
        <p class="page-subtitle">Visão comercial: vendas, entregas e saúde da integração.</p>
    </div>
</div>

<div class="card">
    <h2 class="mb-2">Saúde da integração</h2>
    <div class="table-wrap">
        <table class="data">
            <tbody>
                <tr><th>Loja habilitada</th><td><span class="badge <?= $health['enabled'] ? 'badge-success' : 'badge-muted' ?>"><?= $health['enabled'] ? 'Sim' : 'Não' ?></span></td></tr>
                <tr><th>Gateway</th><td><?= e($health['gateway']) ?></td></tr>
                <tr><th>Ambiente</th><td><span class="badge <?= $health['environment'] === 'production' ? 'badge-danger' : 'badge-warning' ?>"><?= e($health['environment']) ?></span></td></tr>
                <tr><th>Service account Game API</th><td><span class="badge <?= $health['game_service'] ? 'badge-success' : 'badge-warning' ?>"><?= $health['game_service'] ? 'Configurada' : 'Não configurada' ?></span></td></tr>
            </tbody>
        </table>
    </div>
    <?php if (!$health['game_service']): ?>
        <p class="muted mt-2" style="font-size:.9rem;">
            A concessão de itens depende dos endpoints comerciais da Game API (ver
            <code>docs/api/commercial-integration.md</code>). Enquanto não configurada/implementada,
            pedidos pagos ficam em <strong>entrega pendente</strong> para reprocessamento.
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="mb-2">Números</h2>
    <div class="table-wrap">
        <table class="data">
            <tbody>
                <tr><th>Pedidos pagos</th><td><?= (int) $metrics['orders_paid'] ?></td></tr>
                <tr><th>Pedidos aguardando pagamento</th><td><?= (int) $metrics['orders_pending'] ?></td></tr>
                <tr><th>Pedidos reembolsados</th><td><?= (int) $metrics['orders_refunded'] ?></td></tr>
                <tr><th>Entregas pendentes</th><td><?= (int) $metrics['fulfill_pending'] ?></td></tr>
                <tr><th>Entregas com falha</th><td><span class="badge <?= $metrics['fulfill_failed'] > 0 ? 'badge-danger' : 'badge-muted' ?>"><?= (int) $metrics['fulfill_failed'] ?></span></td></tr>
                <tr><th>Pago sem entrega (reconciliação)</th><td><span class="badge <?= $metrics['reconcile_count'] > 0 ? 'badge-warning' : 'badge-muted' ?>"><?= (int) $metrics['reconcile_count'] ?></span></td></tr>
                <tr><th>Receita aprovada (30 dias)</th><td><?= e(OrderPresenter::money((int) $metrics['revenue_30d_cents'])) ?></td></tr>
            </tbody>
        </table>
    </div>
    <div class="mt-2" style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="/admin/loja/pedidos" class="btn btn-secondary btn-sm">Pedidos</a>
        <a href="/admin/loja/reconciliacao" class="btn btn-secondary btn-sm">Reconciliação</a>
        <a href="/admin/loja/webhooks" class="btn btn-secondary btn-sm">Webhooks</a>
    </div>
</div>
