<?php
ob_start();
?>
<div class="card" style="text-align:center;">
    <div style="font-size:3.5rem;margin-bottom:1rem;">🔒</div>
    <h1>Sistema já instalado</h1>
    <p class="lead">O instalador está bloqueado porque o sistema já foi configurado.</p>
    <p style="margin-bottom:2rem;color:#9aa4bd;">
        Para executar a instalação novamente, remova o arquivo <code>config/installed.lock</code> do servidor.
        Esta é uma ação administrativa explícita e deve ser feita com cuidado.
    </p>
    <a href="/admin/login" class="btn">Ir para o painel</a>
</div>
<?php
$slot = ob_get_clean();
$title = 'Sistema instalado';
include __DIR__ . '/_shell.php';
