<?php
/**
 * @var array $policy
 * @var array $versions
 */
$canManage = has_permission('privacy.manage');
$statusLabels = ['draft' => 'Rascunho', 'published' => 'Publicada', 'archived' => 'Arquivada'];
$statusBadge = ['draft' => 'badge-warning', 'published' => 'badge-success', 'archived' => 'badge-muted'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Histórico: <?= e($policy['title']) ?></h1>
        <p class="page-subtitle">Versões do documento. Versões publicadas nunca são apagadas.</p>
    </div>
    <?php if ($canManage): ?>
        <a href="/admin/privacidade/documentos/<?= (int) $policy['id'] ?>/editar" class="btn btn-primary">Nova versão</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($versions)): ?>
        <div class="empty-state"><p>Nenhuma versão criada ainda.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Versão</th><th>Status</th><th>Vigência</th><th>Publicada por</th><th>Publicada em</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($versions as $v): ?>
                        <tr>
                            <td><strong>v<?= e($v['version']) ?></strong></td>
                            <td><span class="badge <?= $statusBadge[$v['status']] ?? 'badge-muted' ?>"><?= e($statusLabels[$v['status']] ?? $v['status']) ?></span></td>
                            <td><?= !empty($v['effective_at']) ? e(format_date($v['effective_at'], 'd/m/Y H:i')) : '—' ?></td>
                            <td><?= !empty($v['publisher_name']) ? e($v['publisher_name']) : '—' ?></td>
                            <td><?= !empty($v['published_at']) ? e(format_date($v['published_at'], 'd/m/Y H:i')) : '—' ?></td>
                            <td class="actions">
                                <?php if ($canManage && $v['status'] === 'draft'): ?>
                                    <form method="post" action="/admin/privacidade/documentos/<?= (int) $policy['id'] ?>/versoes/<?= (int) $v['id'] ?>/publicar" id="pub<?= (int) $v['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-confirm="Publicar a versão v<?= e($v['version']) ?>? Isso a torna vigente e arquiva a anterior." data-form="pub<?= (int) $v['id'] ?>">Publicar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
