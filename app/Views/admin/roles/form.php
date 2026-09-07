<?php
/** @var array|null $role_item */
/** @var array $groups */
/** @var array $selectedPerms */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $role_item !== null;
$action = $isEdit ? '/admin/perfis/' . (int) $role_item['id'] : '/admin/perfis';
$val = fn(string $k, $d = '') => e(old($k, $role_item[$k] ?? $d));
$selectedPerms = array_map('intval', $selectedPerms);
$isSuperAdmin = $isEdit && ($role_item['slug'] ?? '') === 'super-admin';
$isSystem = $isEdit && (int) ($role_item['is_system'] ?? 0) === 1;
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar perfil' : 'Novo perfil' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" data-slug-source value="<?= $val('name') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" data-slug-target value="<?= $val('slug') ?>" <?= $isSystem ? 'readonly' : '' ?> required>
                <?php if (isset($errors['slug'])): ?><div class="field-error"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Descrição</label>
            <input type="text" id="description" name="description" value="<?= $val('description') ?>">
        </div>
    </div>

    <div class="card">
        <div class="card-title">Permissões</div>
        <?php if ($isSuperAdmin): ?>
            <p class="muted">O perfil <strong>Super Administrador</strong> possui acesso total e irrestrito. As permissões não se aplicam a ele.</p>
        <?php else: ?>
            <?php foreach ($groups as $group => $perms): ?>
                <div class="mb-2">
                    <div class="flex items-center justify-between" style="margin-bottom:.5rem;">
                        <strong style="text-transform:capitalize;"><?= e($group) ?></strong>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleGroup('<?= e($group) ?>')">Alternar todos</button>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
                        <?php foreach ($perms as $p): ?>
                            <label class="checkbox-row" style="margin:0;">
                                <input type="checkbox" class="perm-<?= e($group) ?>" name="permissions[]" value="<?= (int) $p['id'] ?>"
                                    <?= in_array((int) $p['id'], $selectedPerms, true) ? 'checked' : '' ?>>
                                <?= e($p['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar' : 'Criar perfil' ?></button>
        <a href="/admin/perfis" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
function toggleGroup(group) {
    var boxes = document.querySelectorAll('.perm-' + group);
    var allChecked = Array.prototype.every.call(boxes, function (b) { return b.checked; });
    boxes.forEach(function (b) { b.checked = !allChecked; });
}
</script>
