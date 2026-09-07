<?php
/**
 * @var array $result  ['data'=>..., 'total'=>..., 'page'=>..., 'per_page'=>..., 'pages'=>...]
 * @var array $filters
 * @var array $statuses
 * @var array $types
 */
$statusLabels = [
    'pending' => 'Pendente', 'in_review' => 'Em análise', 'awaiting_user' => 'Aguardando titular',
    'completed' => 'Concluída', 'rejected' => 'Rejeitada', 'cancelled' => 'Cancelada',
];
$typeLabels = [
    'access' => 'Acesso', 'correction' => 'Correção', 'deletion' => 'Exclusão',
    'portability' => 'Portabilidade', 'consent_revocation' => 'Revogação de consentimento',
    'information' => 'Informação', 'other' => 'Outro',
];
$statusBadge = ['pending' => 'badge-warning', 'in_review' => 'badge-muted', 'awaiting_user' => 'badge-muted', 'completed' => 'badge-success', 'rejected' => 'badge-danger', 'cancelled' => 'badge-muted'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Solicitações de privacidade</h1>
        <p class="page-subtitle">Pedidos de titulares (acesso, correção, exclusão, portabilidade).</p>
    </div>
</div>

<div class="card">
    <form method="get" action="/admin/privacidade/solicitacoes" class="toolbar" style="border-bottom:1px solid var(--border);padding-bottom:1rem;margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
        <select name="status" class="btn btn-secondary btn-sm">
            <option value="">Todos os status</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($statusLabels[$s] ?? $s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="btn btn-secondary btn-sm">
            <option value="">Todos os tipos</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e($typeLabels[$t] ?? $t) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
    </form>

    <?php if (empty($result['data'])): ?>
        <div class="empty-state"><p>Nenhuma solicitação encontrada.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>ID</th><th>Tipo</th><th>Titular</th><th>Status</th><th>Criada</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($result['data'] as $r): ?>
                        <tr>
                            <td><code><?= e($r['public_id']) ?></code></td>
                            <td><?= e($typeLabels[$r['type']] ?? $r['type']) ?></td>
                            <td><?= e(mask_email($r['email'] ?? '')) ?><?php if (!empty($r['player_id'])): ?> <span class="muted">· <?= e($r['player_id']) ?></span><?php endif; ?></td>
                            <td><span class="badge <?= $statusBadge[$r['status']] ?? 'badge-muted' ?>"><?= e($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
                            <td><?= e(format_date($r['created_at'], 'd/m/Y H:i')) ?></td>
                            <td class="actions"><a href="/admin/privacidade/solicitacoes/<?= (int) $r['id'] ?>" class="btn btn-secondary btn-sm">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $result['page'], 'total' => $result['total'], 'perPage' => $result['per_page'], 'baseUrl' => '/admin/privacidade/solicitacoes', 'query' => $filters]) ?>
    <?php endif; ?>
</div>
