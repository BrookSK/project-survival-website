<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Auditoria</h1>
        <p class="page-subtitle">Registro de ações administrativas (<?= (int) $total ?> no total).</p>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/auditoria">
            <input type="text" name="module" class="search" placeholder="Filtrar por módulo (ex.: news)" value="<?= e($filters['module']) ?>">
            <input type="text" name="action" placeholder="Ação (ex.: create)" value="<?= e($filters['action']) ?>">
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="/admin/auditoria" class="btn btn-secondary">Limpar</a>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon">📋</div><p>Nenhum registro de auditoria.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Módulo</th><th>Descrição</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($items as $a): ?>
                    <tr>
                        <td class="muted text-sm"><?= e(format_date($a['created_at'])) ?></td>
                        <td><?= e($a['user_name'] ?? 'Sistema') ?></td>
                        <td><span class="badge badge-muted"><?= e($a['action']) ?></span></td>
                        <td class="muted"><?= e($a['module']) ?></td>
                        <td class="text-sm"><?= e($a['description'] ?? '—') ?></td>
                        <td class="muted text-sm"><?= e($a['ip_address'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/auditoria', 'query' => $filters]) ?>
    <?php endif; ?>
</div>
