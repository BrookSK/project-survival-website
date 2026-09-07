<?php
/** @var array $items */
/** @var array $platforms */
/** @var array $errors */
$errors = $errors ?? [];
$canEdit = has_permission('social.edit');
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Redes sociais</h1>
        <p class="page-subtitle">Links exibidos no rodapé e na home. Arraste para reordenar.</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Links cadastrados</div>
        <?php if (empty($items)): ?>
            <div class="empty-state"><div class="icon">🔗</div><p>Nenhuma rede social cadastrada.</p></div>
        <?php else: ?>
            <ul class="sortable-list" id="socialList" data-reorder-url="/admin/redes-sociais/reordenar">
                <?php foreach ($items as $s): ?>
                    <li class="sortable-item" data-id="<?= (int) $s['id'] ?>">
                        <span class="drag-handle" title="Arrastar">⠿</span>
                        <div class="sortable-body">
                            <strong><?= e($s['label']) ?></strong>
                            <span class="badge badge-muted"><?= e($s['platform']) ?></span>
                            <?php if ((int) $s['is_active'] === 1): ?><span class="badge badge-success">Ativo</span><?php else: ?><span class="badge badge-danger">Inativo</span><?php endif; ?>
                            <div class="muted text-sm" style="width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($s['url']) ?></div>
                        </div>
                        <?php if ($canEdit): ?>
                        <div class="actions">
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick='fillSocial(<?= json_encode(["id"=>(int)$s["id"],"platform"=>$s["platform"],"label"=>$s["label"],"url"=>$s["url"],"is_active"=>(int)$s["is_active"]], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
                            <form method="post" action="/admin/redes-sociais/<?= (int) $s['id'] ?>/excluir" id="del-soc-<?= (int) $s['id'] ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="button" class="btn btn-danger btn-sm" data-confirm="Remover esta rede social?" data-form="del-soc-<?= (int) $s['id'] ?>">Remover</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
    <div class="card">
        <div class="card-title" id="soc-form-title">Nova rede social</div>
        <form method="post" action="/admin/redes-sociais" id="socialForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="platform">Plataforma</label>
                <select id="platform" name="platform" required>
                    <?php foreach ($platforms as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="label">Rótulo <span class="label-hint">(opcional)</span></label>
                <input type="text" id="label" name="label" value="<?= old('label') ?>">
                <?php if (isset($errors['label'])): ?><div class="field-error"><?= e($errors['label']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="url">URL</label>
                <input type="url" id="url" name="url" value="<?= old('url') ?>" placeholder="https://..." required>
                <?php if (isset($errors['url'])): ?><div class="field-error"><?= e($errors['url']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" checked id="soc_active"> Ativo</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="resetSocial()">Limpar</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script>
function fillSocial(s) {
    var f = document.getElementById('socialForm');
    f.action = '/admin/redes-sociais/' + s.id;
    document.getElementById('soc-form-title').textContent = 'Editar rede social';
    f.platform.value = s.platform;
    f.label.value = s.label || '';
    f.url.value = s.url || '';
    f.is_active.checked = s.is_active === 1;
    window.scrollTo({ top: f.offsetTop - 80, behavior: 'smooth' });
}
function resetSocial() {
    var f = document.getElementById('socialForm');
    f.action = '/admin/redes-sociais';
    document.getElementById('soc-form-title').textContent = 'Nova rede social';
    f.reset();
}
</script>
