<?php
/** @var array $errors */
$errors = $errors ?? [];
?>
<section class="page-hero">
    <div class="container">
        <h1>Criar conta</h1>
        <p>Crie sua conta de jogador do Project Survival.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container" style="max-width:460px;">
        <div class="form-card">
            <form method="post" action="/criar-conta" novalidate>
                <?= csrf_field() ?>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" autocomplete="email" required autofocus>
                    <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" value="<?= old('username') ?>" autocomplete="username" required>
                    <?php if (isset($errors['username'])): ?><div class="form-error"><?= e($errors['username']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
                    <div class="label-hint">Mínimo de 8 caracteres.</div>
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirmar senha</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Criar conta</button>
            </form>
            <p class="mt-3" style="text-align:center;">
                Já tem conta? <a href="/login">Entrar</a>
            </p>
        </div>
    </div>
</section>
