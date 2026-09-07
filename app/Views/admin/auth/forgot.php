<?php
/** @var array $errors */
$errors = $errors ?? [];
ob_start();
?>
<p style="color:#8b95b0;font-size:0.9rem;margin-bottom:1.5rem;text-align:center;">
    Informe seu e-mail e enviaremos as instruções para redefinir sua senha.
</p>
<form method="post" action="/admin/esqueci-senha" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn">Enviar instruções</button>
</form>
<div class="links">
    <a href="/admin/login">Voltar para o login</a>
</div>
<?php
$slot = ob_get_clean();
include __DIR__ . '/_shell.php';
