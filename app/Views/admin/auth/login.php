<?php
/** @var array $errors */
$errors = $errors ?? [];
ob_start();
?>
<form method="post" action="/admin/login" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus autocomplete="username">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="field">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn">Entrar</button>
</form>
<div class="links">
    <a href="/admin/esqueci-senha">Esqueci minha senha</a>
</div>
<?php
$slot = ob_get_clean();
include __DIR__ . '/_shell.php';
