<?php
/** @var array $errors */
$errors = $errors ?? [];
?>
<section class="page-hero">
    <div class="container"><h1>Segurança</h1><p>Altere a senha da sua conta.</p></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'security']) ?>
        <div class="account-content">
            <div class="form-card" style="max-width:480px;">
                <h2 class="mb-2">Alterar senha</h2>
                <form method="post" action="/conta/seguranca" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-field">
                        <label for="current_password">Senha atual</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                        <?php if (isset($errors['current_password'])): ?><div class="form-error"><?= e($errors['current_password']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="new_password">Nova senha</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                        <?php if (isset($errors['new_password'])): ?><div class="form-error"><?= e($errors['new_password']) ?></div><?php endif; ?>
                        <div class="label-hint">Mínimo de 8 caracteres.</div>
                    </div>
                    <div class="form-field">
                        <label for="new_password_confirmation">Confirmar nova senha</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Salvar nova senha</button>
                </form>
                <p class="muted mt-3" style="font-size:.88rem;">A alteração é feita diretamente na conta do jogo.</p>
            </div>
        </div>
    </div>
</section>
