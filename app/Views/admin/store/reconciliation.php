<?php
/**
 * @var array $orders  pedidos pagos sem fulfillment concluído
 */
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Reconciliação</h1>
        <p class="page-subtitle">Pedidos pagos cuja entrega ainda não foi concluída pela API do jogo.</p>
    </div>
</div>

<div class="card">
    <?php if (empty($orders)): ?>
        <div class="empty-state"><p>Tudo reconciliado: nenhum pedido pago sem entrega.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Referência</th><th>Jogador</th><th>Total</th><th>Entrega</th><th>Pago em</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><code><?= e($o['reference']) ?></code></td>
                            <td><?= e($o['player_id']) ?></td>
                            <td><?= e(OrderPresenter::money((int) $o['total_cents'], $o['currency'])) ?></td>
                            <td><span class="badge badge-<?= e(OrderPresenter::badge($o['fulfillment_status'])) ?>"><?= e(OrderPresenter::fulfillmentLabel($o['fulfillment_status'])) ?></span></td>
                            <td><?= e($o['paid_at'] ? format_date($o['paid_at'], 'd/m/Y H:i') : '—') ?></td>
                            <td class="actions">
                                <form method="post" action="/admin/loja/pedidos/<?= (int) $o['id'] ?>/reprocessar">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-primary btn-sm" data-confirm="Reprocessar entrega deste pedido?" data-form>Reprocessar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
