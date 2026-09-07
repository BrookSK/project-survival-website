<?php
/** @var array $checks */
$badges = [
    'ok'      => ['OK', 'badge-success', '✓'],
    'warning' => ['Atenção', 'badge-warning', '!'],
    'error'   => ['Erro', 'badge-danger', '✕'],
];
$errors = count(array_filter($checks, fn($c) => $c['status'] === 'error'));
$warnings = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Diagnóstico</h1>
        <p class="page-subtitle">
            <?php if ($errors): ?>
                <span class="badge badge-danger"><?= $errors ?> erro(s)</span>
            <?php endif; ?>
            <?php if ($warnings): ?>
                <span class="badge badge-warning"><?= $warnings ?> atenção</span>
            <?php endif; ?>
            <?php if (!$errors && !$warnings): ?>
                <span class="badge badge-success">Tudo OK</span>
            <?php endif; ?>
        </p>
    </div>
    <a href="/admin/sistema" class="btn btn-secondary">← Sistema</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Verificação</th><th>Status</th><th>Detalhe</th></tr></thead>
            <tbody>
            <?php foreach ($checks as $c):
                [$label, $badge, $icon] = $badges[$c['status']] ?? $badges['warning']; ?>
                <tr>
                    <td><?= e($c['label']) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $icon ?> <?= $label ?></span></td>
                    <td class="muted text-sm"><?= e($c['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
