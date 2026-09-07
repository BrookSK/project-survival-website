<?php
/** @var array $items */
/** @var array $errors */
$errors = $errors ?? [];
$canEdit = has_permission('redirects.edit');
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Redirecionamentos</h1>
        <p class="page-subtitle">Redirecione URLs antigas para novas (301/302).</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Redirects cadastrados</div>
        <?php if (empty($items)): ?>
            <div class="empty-state"><div class="icon">↪️</div><p>Nenhum redirecionamento.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>De</th><th>Para</th><th>Código</th><th>Hits</th><th>Ativo</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $r): ?>
                        <tr>
                            <td class="muted"><?= e($r['from_path']) ?></td>
                            <td class="muted" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($r['to_url']) ?></td>
                            <td><span class="badge badge-muted"><?= (int) $r['status_code'] ?></span></td>
                            <td class="muted"><?= (int) $r['hits'] ?></td>
                            <td><?php if ((int) $r['is_active'] === 1): ?><span class="badge badge-success">Sim</span><?php else: ?><span class="badge badge-muted">Não</span><?php endif; ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <div class="actions">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        onclick='fillRedirect(<?= json_encode(["id"=>(int)$r["id"],"from"=>$r["from_path"],"to"=>$r["to_url"],"code"=>(int)$r["status_code"],"active"=>(int)$r["is_active"]], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
                                    <form method="post" action="/admin/redirects/<?= (int) $r['id'] ?>/excluir" id="del-rd-<?= (int) $r['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm" data-confirm="Remover este redirect?" data-form="del-rd-<?= (int) $r['id'] ?>">Remover</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
    <div class="card">
        <div class="card-title" id="rd-form-title">Novo redirect</div>
        <form method="post" action="/admin/redirects" id="redirectForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="from_path">Caminho de origem <span class="label-hint">(ex.: /noticia-antiga)</span></label>
                <input type="text" id="from_path" name="from_path" value="<?= old('from_path') ?>" required>
                <?php if (isset($errors['from_path'])): ?><div class="field-error"><?= e($errors['from_path']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="to_url">Destino <span class="label-hint">(caminho interno ou URL completa)</span></label>
                <input type="text" id="to_url" name="to_url" value="<?= old('to_url') ?>" required>
                <?php if (isset($errors['to_url'])): ?><div class="field-error"><?= e($errors['to_url']) ?></div><?php endif; ?>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="status_code">Tipo</label>
                    <select id="status_code" name="status_code">
                        <option value="301">301 (permanente)</option>
                        <option value="302">302 (temporário)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top:1.8rem;">
                    <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" checked id="rd_active"> Ativo</label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="resetRedirect()">Limpar</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function fillRedirect(r) {
    var f = document.getElementById('redirectForm');
    f.action = '/admin/redirects/' + r.id;
    document.getElementById('rd-form-title').textContent = 'Editar redirect';
    f.from_path.value = r.from; f.to_url.value = r.to; f.status_code.value = r.code; f.is_active.checked = r.active === 1;
    window.scrollTo({ top: f.offsetTop - 80, behavior: 'smooth' });
}
function resetRedirect() {
    var f = document.getElementById('redirectForm');
    f.action = '/admin/redirects';
    document.getElementById('rd-form-title').textContent = 'Novo redirect';
    f.reset();
}
</script>
