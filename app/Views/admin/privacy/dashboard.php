<?php
/**
 * @var array $statusCounts
 * @var array $consents  [type => ['granted'=>int,'total'=>int]]
 * @var array $exports
 */
$statusLabels = [
    'pending' => 'Pendentes', 'in_review' => 'Em análise', 'awaiting_user' => 'Aguardando titular',
    'completed' => 'Concluídas', 'rejected' => 'Rejeitadas', 'cancelled' => 'Canceladas',
];
$exportStatus = ['pending' => 'Pendente', 'ready' => 'Pronta', 'expired' => 'Expirada', 'failed' => 'Falhou'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Privacidade</h1>
        <p class="page-subtitle">Solicitações de titulares, consentimentos e exportações.</p>
    </div>
    <?php if (has_permission('privacy.requests')): ?>
        <a href="/admin/privacidade/solicitacoes" class="btn btn-primary">Ver solicitações</a>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Solicitações por status</div>
        <table class="data">
            <tbody>
                <?php foreach ($statusCounts as $s => $n): ?>
                    <tr><td><?= e($statusLabels[$s] ?? $s) ?></td><td><strong><?= (int) $n ?></strong></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Consentimentos</div>
        <?php if (empty($consents)): ?>
            <p class="muted">Nenhum consentimento registrado ainda.</p>
        <?php else: ?>
            <table class="data">
                <thead><tr><th>Tipo</th><th>Concedidos</th><th>Total</th></tr></thead>
                <tbody>
                    <?php foreach ($consents as $type => $c): ?>
                        <tr><td><?= e($type) ?></td><td><?= (int) $c['granted'] ?></td><td><?= (int) $c['total'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3">
    <div class="card-title">Exportações recentes</div>
    <p class="muted mb-2" style="font-size:.85rem;">Os arquivos ficam em armazenamento privado e expiram. Tokens de download nunca são exibidos.</p>
    <?php if (empty($exports)): ?>
        <div class="empty-state"><p>Nenhuma exportação gerada.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Jogador</th><th>Status</th><th>Expira</th><th>Baixada</th><th>Criada</th></tr></thead>
                <tbody>
                    <?php foreach ($exports as $x): ?>
                        <tr>
                            <td><?= e($x['player_id']) ?></td>
                            <td><span class="badge badge-muted"><?= e($exportStatus[$x['status']] ?? $x['status']) ?></span></td>
                            <td><?= !empty($x['expires_at']) ? e(format_date($x['expires_at'], 'd/m/Y H:i')) : '—' ?></td>
                            <td><?= !empty($x['downloaded_at']) ? e(format_date($x['downloaded_at'], 'd/m/Y H:i')) : '—' ?></td>
                            <td><?= e(format_date($x['created_at'], 'd/m/Y H:i')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
