<?php
/** @var array $rows  [ ['policy'=>..., 'published'=>...|null], ... ] */
$canManage = has_permission('privacy.manage');
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Documentos legais</h1>
        <p class="page-subtitle">Política de Privacidade, Termos e políticas relacionadas, com versionamento.</p>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:1.25rem;">
    <span>Os documentos gerados/editados aqui devem ser <strong>revisados juridicamente</strong> antes da publicação definitiva. A implementação técnica não substitui aconselhamento jurídico.</span>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data">
        <thead>
            <tr><th>Documento</th><th>URL pública</th><th>Versão vigente</th><th>Vigência</th><th>Aceite</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <?php $p = $r['policy']; $pub = $r['published']; ?>
                <tr>
                    <td><strong><?= e($p['title']) ?></strong></td>
                    <td><a href="/<?= e($p['slug']) ?>" target="_blank" rel="noopener">/<?= e($p['slug']) ?></a></td>
                    <td>
                        <?php if ($pub): ?>
                            <span class="badge badge-success">v<?= e($pub['version']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-warning">Não publicado</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $pub && !empty($pub['effective_at']) ? e(format_date($pub['effective_at'], 'd/m/Y')) : '—' ?></td>
                    <td><?= (int) $p['is_required'] === 1 ? '<span class="badge badge-muted">Obrigatório</span>' : '—' ?></td>
                    <td class="actions">
                        <a href="/admin/privacidade/documentos/<?= (int) $p['id'] ?>/historico" class="btn btn-secondary btn-sm">Histórico</a>
                        <?php if ($canManage): ?>
                            <a href="/admin/privacidade/documentos/<?= (int) $p['id'] ?>/editar" class="btn btn-primary btn-sm">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
