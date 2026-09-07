<?php
/** @var array|null $user_item */
/** @var array $allRoles */
/** @var array $selectedRoles */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $user_item !== null;
$action = $isEdit ? '/admin/usuarios/' . (int) $user_item['id'] : '/admin/usuarios';
$val = fn(string $k, $d = '') => e(old($k, $user_item[$k] ?? $d));
$selectedRoles = array_map('intval', $selectedRoles);
$active = (int) old('is_active', $user_item['is_active'] ?? 1);
$isSelf = $isEdit && (int) $user_item['id'] === (int) (auth_user()['id'] ?? 0);
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar usuário' : 'Novo usuário' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= $val('email') ?>" required>
                <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Senha <span class="label-hint"><?= $isEdit ? '(deixe em branco para manter)' : '(mín. 8 caracteres)' ?></span></label>
                <input type="password" id="password" name="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirmar senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                Usuário ativo <?php if ($isSelf): ?><span class="label-hint">(não é possível desativar a própria conta)</span><?php endif; ?>
            </label>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Perfis de acesso</div>
        <?php if (empty($allRoles)): ?>
            <p class="muted">Nenhum perfil cadastrado.</p>
        <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;gap:1rem;">
                <?php foreach ($allRoles as $role): ?>
                    <label class="checkbox-row" style="margin:0;">
                        <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>"
                            <?= in_array((int) $role['id'], $selectedRoles, true) ? 'checked' : '' ?>>
                        <?= e($role['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar' : 'Criar usuário' ?></button>
        <a href="/admin/usuarios" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
