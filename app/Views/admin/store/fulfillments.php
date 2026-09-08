<?php
/**
 * @var array $due  fulfillments pendentes/prontos para retry
 */
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Fulfillments</h1>
        <p class="page-subtitle">Concessões pendentes de entrega. O reprocessamento é feito no pedido.</p>
    </div>
</div>

<div class="card">
    <?php if (empty($due)): ?>
        <div class="empty-state"><p>Nenhum fulfillment pendente de retry.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Produto</th><th>Jogador</th><th>Status</th><th>Tentativas</th><th>Próxima tentativa</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($due as $f): ?>
                        <tr>
                            <td><?= e($f['product_id']) ?></td>
                            <td><?= e($f['player_id']) ?></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($f['status'])) ?>"><?= e(OrderPresenter::fulfillmentLabel($f['status'])) ?></span></td>
                            <td><?= (int) $f['attempts'] ?>/<?= (int) $f['max_attempts'] ?></td>
                            <td><?= e($f['next_attempt_at'] ? format_date($f['next_attempt_at'], 'd/m/Y H:i') : '—') ?></td>
                            <td class="actions"><a href="/admin/loja/pedidos/<?= (int) $f['order_id'] ?>" class="btn btn-secondary btn-sm">Ver pedido</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
