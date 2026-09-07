<?php
/** @var array $checks */
/** @var bool $canProceed */
/** @var array $errors */
$errors = $errors ?? [];
ob_start();
?>
<div class="card">
    <h1>Instalação do Sistema</h1>
    <p class="lead">Bem-vindo! Vamos configurar o website oficial do jogo em poucos passos.</p>

    <h2>Requisitos do servidor</h2>
    <?php foreach ($checks as $c): ?>
        <div class="check">
            <span class="status <?= $c['pass'] ? 'ok' : 'fail' ?>"><?= $c['pass'] ? '✓' : '✕' ?></span>
            <span><?= e($c['label']) ?></span>
            <span class="detail"><?= e($c['detail']) ?></span>
        </div>
    <?php endforeach; ?>
    <?php if (!$canProceed): ?>
        <div class="alert alert-error" style="margin-top:1.5rem;">
            Alguns requisitos não foram atendidos. Corrija-os e recarregue a página para continuar.
        </div>
    <?php endif; ?>
</div>

<?php if ($canProceed): ?>
<form method="post" action="/install" novalidate>
    <?= csrf_field() ?>

    <div class="card">
        <h2>Banco de dados</h2>
        <div class="row">
            <div class="field">
                <label for="db_host">Host</label>
                <input type="text" id="db_host" name="db_host" value="<?= old('db_host', '127.0.0.1') ?>" required>
                <?php if (isset($errors['db_host'])): ?><div class="field-error"><?= e($errors['db_host']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="db_port">Porta</label>
                <input type="number" id="db_port" name="db_port" value="<?= old('db_port', '3306') ?>" required>
                <?php if (isset($errors['db_port'])): ?><div class="field-error"><?= e($errors['db_port']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="field">
            <label for="db_name">Nome do banco</label>
            <input type="text" id="db_name" name="db_name" value="<?= old('db_name') ?>" required>
            <?php if (isset($errors['db_name'])): ?><div class="field-error"><?= e($errors['db_name']) ?></div><?php endif; ?>
        </div>
        <div class="row">
            <div class="field">
                <label for="db_user">Usuário</label>
                <input type="text" id="db_user" name="db_user" value="<?= old('db_user', 'root') ?>" required>
                <?php if (isset($errors['db_user'])): ?><div class="field-error"><?= e($errors['db_user']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="db_pass">Senha</label>
                <input type="password" id="db_pass" name="db_pass" value="" autocomplete="new-password">
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Administrador inicial</h2>
        <div class="field">
            <label for="admin_name">Nome</label>
            <input type="text" id="admin_name" name="admin_name" value="<?= old('admin_name') ?>" required>
            <?php if (isset($errors['admin_name'])): ?><div class="field-error"><?= e($errors['admin_name']) ?></div><?php endif; ?>
        </div>
        <div class="field">
            <label for="admin_email">E-mail</label>
            <input type="email" id="admin_email" name="admin_email" value="<?= old('admin_email') ?>" required>
            <?php if (isset($errors['admin_email'])): ?><div class="field-error"><?= e($errors['admin_email']) ?></div><?php endif; ?>
        </div>
        <div class="row">
            <div class="field">
                <label for="admin_password">Senha (mín. 8 caracteres)</label>
                <input type="password" id="admin_password" name="admin_password" required autocomplete="new-password">
                <?php if (isset($errors['admin_password'])): ?><div class="field-error"><?= e($errors['admin_password']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="admin_password_confirmation">Confirmar senha</label>
                <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" required autocomplete="new-password">
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Dados do site</h2>
        <div class="field">
            <label for="site_name">Nome do site</label>
            <input type="text" id="site_name" name="site_name" value="<?= old('site_name', 'Nome do Jogo') ?>" required>
            <?php if (isset($errors['site_name'])): ?><div class="field-error"><?= e($errors['site_name']) ?></div><?php endif; ?>
        </div>
        <div class="field">
            <label for="site_url">URL do site</label>
            <input type="text" id="site_url" name="site_url" value="<?= old('site_url', 'http://localhost') ?>">
        </div>
        <button type="submit" class="btn btn-block">Instalar sistema</button>
    </div>
</form>
<?php endif; ?>
<?php
$slot = ob_get_clean();
$title = 'Instalação';
include __DIR__ . '/_shell.php';
