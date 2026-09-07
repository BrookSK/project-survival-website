<?php
/** @var string $adminEmail */
ob_start();
?>
<div class="card" style="text-align:center;">
    <div style="font-size:3.5rem;margin-bottom:1rem;">🎉</div>
    <h1>Instalação concluída!</h1>
    <p class="lead">O sistema foi instalado com sucesso e está pronto para uso.</p>
    <p style="margin-bottom:2rem;color:#9aa4bd;">
        Acesse o painel administrativo com o e-mail <strong><?= e($adminEmail) ?></strong> e a senha que você definiu.
    </p>
    <a href="/admin/login" class="btn">Ir para o painel</a>
    <p style="margin-top:1.5rem;font-size:.85rem;color:#6b7590;">
        Por segurança, o instalador foi bloqueado. Para reinstalar, remova o arquivo <code>config/installed.lock</code>.
    </p>
</div>
<?php
$slot = ob_get_clean();
$title = 'Instalação concluída';
include __DIR__ . '/_shell.php';
