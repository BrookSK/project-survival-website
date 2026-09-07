<?php
/** @var array $errors */
$errors = $errors ?? [];
?>
<section class="page-hero">
    <div class="container">
        <h1>Entrar</h1>
        <p>Acesse sua conta de jogador do Project Survival.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container" style="max-width:460px;">
        <div class="form-card">
            <form method="post" action="/login" novalidate>
                <?= csrf_field() ?>
                <div class="form-field">
                    <label for="identifier">E-mail ou usuário</label>
                    <input type="text" id="identifier" name="identifier" value="<?= old('identifier') ?>" autocomplete="username" required autofocus>
                    <?php if (isset($errors['identifier'])): ?><div class="form-error"><?= e($errors['identifier']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Entrar</button>
            </form>
            <p class="mt-3" style="text-align:center;">
                Não tem conta? <a href="/criar-conta">Criar conta</a>
            </p>
        </div>
    </div>
</section>
