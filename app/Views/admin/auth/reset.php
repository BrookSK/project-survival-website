<?php
/** @var array $errors */
/** @var string $token */
$errors = $errors ?? [];
ob_start();
?>
<form method="post" action="/admin/redefinir-senha" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="field">
        <label for="password">Nova senha</label>
        <input type="password" id="password" name="password" required autofocus autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <div class="field">
        <label for="password_confirmation">Confirmar nova senha</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn">Redefinir senha</button>
</form>
<div class="links">
    <a href="/admin/login">Voltar para o login</a>
</div>
<?php
$slot = ob_get_clean();
include __DIR__ . '/_shell.php';
