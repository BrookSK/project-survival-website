<?php
/** @var array $info */
/** @var array $pending */
/** @var int $executedCount */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Sistema</h1>
        <p class="page-subtitle">Informações da aplicação, banco e migrations.</p>
    </div>
    <a href="/admin/diagnostico" class="btn btn-secondary">Diagnóstico</a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Informações</div>
        <table class="data">
            <tbody>
                <tr><td>Versão da aplicação</td><td><strong><?= e($info['app_version']) ?></strong></td></tr>
                <tr><td>PHP</td><td><?= e($info['php_version']) ?></td></tr>
                <tr><td>Banco de dados</td><td><?= e($info['db_version']) ?></td></tr>
                <tr><td>Ambiente</td><td><?= e($info['environment']) ?></td></tr>
                <tr><td>Timezone</td><td><?= e($info['timezone']) ?></td></tr>
                <tr><td>GD (imagens)</td><td><?= e($info['gd']) ?></td></tr>
                <tr><td>WebP</td><td><?= e($info['webp']) ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Migrations</div>
        <p class="muted mb-2"><?= (int) $executedCount ?> migration(s) executada(s).</p>
        <?php if (empty($pending)): ?>
            <div class="alert alert-success"><span>✓ Banco atualizado. Nenhuma migration pendente.</span></div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom:1rem;"><span><?= count($pending) ?> migration(s) pendente(s):</span></div>
            <ul style="margin:0 0 1rem 1.25rem;color:var(--text-muted);font-size:.9rem;">
                <?php foreach ($pending as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
            </ul>
            <?php if (has_permission('system.manage')): ?>
                <form method="post" action="/admin/sistema/migrar" id="migrateForm">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-primary" data-confirm="Executar as migrations pendentes agora? Faça backup do banco antes." data-form="migrateForm">Executar migrations pendentes</button>
                </form>
            <?php else: ?>
                <p class="muted text-sm">Você não tem permissão para executar migrations.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (has_permission('system.manage')): ?>
<div class="card mt-3">
    <div class="card-title">Manutenção</div>
    <p class="muted mb-2">Limpe o cache de dados (configurações, menus e sitemap) caso alterações não apareçam imediatamente no site.</p>
    <form method="post" action="/admin/sistema/cache/limpar" id="clearCacheForm">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-secondary" data-confirm="Limpar o cache de dados da aplicação?" data-form="clearCacheForm">Limpar cache</button>
    </form>
</div>
<?php endif; ?>
