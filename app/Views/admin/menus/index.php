<?php
/** @var array $menus */
/** @var array $itemsByMenu */
/** @var array $errors */
$errors = $errors ?? [];
$canEdit = has_permission('menus.edit');
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Menus</h1>
        <p class="page-subtitle">Gerencie os links de navegação do site.</p>
    </div>
</div>

<?php foreach ($menus as $menu): $items = $itemsByMenu[$menu['id']] ?? []; ?>
<div class="card">
    <div class="card-title"><?= e($menu['name']) ?> <span class="muted text-sm">(<?= e($menu['location']) ?>)</span></div>

    <?php if (empty($items)): ?>
        <p class="muted mb-2">Nenhum item neste menu.</p>
    <?php else: ?>
        <div class="table-wrap mb-2">
            <table class="data">
                <thead><tr><th>Rótulo</th><th>URL</th><th>Ordem</th><th>Ativo</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= e($it['label']) ?></td>
                        <td class="muted"><?= e($it['url']) ?></td>
                        <td class="muted"><?= (int) $it['sort_order'] ?></td>
                        <td>
                            <?php if ((int) $it['is_active'] === 1): ?>
                                <span class="badge badge-success">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($canEdit): ?>
                            <div class="actions">
                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick='fillMenuItem(<?= json_encode(["id"=>(int)$it["id"],"menu_id"=>(int)$it["menu_id"],"label"=>$it["label"],"url"=>$it["url"],"target"=>$it["target"],"sort_order"=>(int)$it["sort_order"],"is_active"=>(int)$it["is_active"]], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
                                <form method="post" action="/admin/menus/itens/<?= (int) $it['id'] ?>/excluir" id="del-mi-<?= (int) $it['id'] ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="button" class="btn btn-danger btn-sm" data-confirm="Remover este item?" data-form="del-mi-<?= (int) $it['id'] ?>">Remover</button>
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
<?php endforeach; ?>

<?php if ($canEdit): ?>
<div class="card">
    <div class="card-title" id="mi-form-title">Adicionar item</div>
    <form method="post" action="/admin/menus/itens" id="menuItemForm" novalidate>
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="menu_id">Menu</label>
                <select id="menu_id" name="menu_id" required>
                    <?php foreach ($menus as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="label">Rótulo</label>
                <input type="text" id="label" name="label" value="<?= old('label') ?>" required>
                <?php if (isset($errors['label'])): ?><div class="field-error"><?= e($errors['label']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="url">URL</label>
                <input type="text" id="url" name="url" value="<?= old('url') ?>" placeholder="/sobre ou https://..." required>
                <?php if (isset($errors['url'])): ?><div class="field-error"><?= e($errors['url']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="target">Abrir em</label>
                <select id="target" name="target">
                    <option value="_self">Mesma aba</option>
                    <option value="_blank">Nova aba</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="sort_order">Ordem</label>
                <input type="number" id="sort_order" name="sort_order" value="0">
            </div>
            <div class="form-group">
                <label class="checkbox-row" style="margin-top:1.8rem;">
                    <input type="checkbox" name="is_active" value="1" checked> Ativo
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar item</button>
            <button type="button" class="btn btn-secondary" onclick="resetMenuItem()">Limpar</button>
        </div>
    </form>
</div>

<script>
function fillMenuItem(it) {
    var f = document.getElementById('menuItemForm');
    f.action = '/admin/menus/itens/' + it.id;
    document.getElementById('mi-form-title').textContent = 'Editar item';
    f.menu_id.value = it.menu_id;
    f.label.value = it.label || '';
    f.url.value = it.url || '';
    f.target.value = it.target || '_self';
    f.sort_order.value = it.sort_order || 0;
    f.is_active.checked = it.is_active === 1;
    window.scrollTo({ top: f.offsetTop - 80, behavior: 'smooth' });
}
function resetMenuItem() {
    var f = document.getElementById('menuItemForm');
    f.action = '/admin/menus/itens';
    document.getElementById('mi-form-title').textContent = 'Adicionar item';
    f.reset();
}
</script>
<?php endif; ?>
