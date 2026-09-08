<?php
/**
 * @var array $result
 */
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Transações</h1>
        <p class="page-subtitle">Transações de pagamento junto ao gateway. Nenhum segredo é exibido.</p>
    </div>
</div>

<div class="card">
    <?php if (empty($result['data'])): ?>
        <div class="empty-state"><p>Nenhuma transação.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>ID externo</th><th>Gateway</th><th>Método</th><th>Status</th><th>Valor</th><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach ($result['data'] as $t): ?>
                        <tr>
                            <td><code><?= e($t['external_id'] ?? '—') ?></code></td>
                            <td><?= e($t['gateway']) ?></td>
                            <td><?= e($t['method'] ?? '—') ?></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($t['status'])) ?>"><?= e(OrderPresenter::paymentLabel($t['status'])) ?></span></td>
                            <td><?= e(OrderPresenter::money((int) $t['amount_cents'], $t['currency'])) ?></td>
                            <td><?= e(format_date($t['created_at'], 'd/m/Y H:i')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $result['page'], 'total' => $result['total'], 'perPage' => $result['per_page'], 'baseUrl' => '/admin/loja/transacoes', 'query' => []]) ?>
    <?php endif; ?>
</div>
