<?php
/**
 * @var array $result
 */
$badge = ['processed' => 'badge-success', 'received' => 'badge-warning', 'ignored' => 'badge-muted', 'invalid' => 'badge-danger', 'failed' => 'badge-danger'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Webhooks</h1>
        <p class="page-subtitle">Eventos recebidos do gateway. Assinatura validada; payload nunca é prova.</p>
    </div>
</div>

<div class="card">
    <?php if (empty($result['data'])): ?>
        <div class="empty-state"><p>Nenhum webhook registrado.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Provedor</th><th>Event ID</th><th>Tipo</th><th>Assinatura</th><th>Status</th><th>Recebido</th></tr></thead>
                <tbody>
                    <?php foreach ($result['data'] as $w): ?>
                        <tr>
                            <td><?= e($w['provider']) ?></td>
                            <td><code><?= e($w['event_id']) ?></code></td>
                            <td><?= e($w['event_type'] ?? '—') ?></td>
                            <td><span class="badge <?= ((int) $w['signature_valid'] === 1) ? 'badge-success' : 'badge-danger' ?>"><?= ((int) $w['signature_valid'] === 1) ? 'Válida' : 'Inválida' ?></span></td>
                            <td><span class="badge <?= $badge[$w['status']] ?? 'badge-muted' ?>"><?= e($w['status']) ?></span></td>
                            <td><?= e(format_date($w['received_at'], 'd/m/Y H:i')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $result['page'], 'total' => $result['total'], 'perPage' => $result['per_page'], 'baseUrl' => '/admin/loja/webhooks', 'query' => []]) ?>
    <?php endif; ?>
</div>
