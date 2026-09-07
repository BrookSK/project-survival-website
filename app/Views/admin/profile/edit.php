<?php
/** @var array $user */
/** @var array $errors */
$errors = $errors ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Meu perfil</h1>
        <p class="page-subtitle">Atualize seus dados de acesso.</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Dados pessoais</div>
        <form method="post" action="/admin/perfil" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" value="<?= e($user['name'] ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>
                <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar dados</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Alterar senha</div>
        <form method="post" action="/admin/perfil/senha" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="current_password">Senha atual</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                <?php if (isset($errors['current_password'])): ?><div class="field-error"><?= e($errors['current_password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Nova senha <span class="label-hint">(mín. 8 caracteres)</span></label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirmar nova senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Alterar senha</button>
            </div>
        </form>
    </div>
</div>
