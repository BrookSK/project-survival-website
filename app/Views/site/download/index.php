<?php
/**
 * Página pública de download.
 *
 * @var \App\Services\GameApi\ReleaseInformation|null $release
 * @var string $channel
 * @var bool $stale
 * @var bool $maintenance
 * @var string $downloadRoute  Rota permanente interna (/download/project-survival)
 */
$release = $release ?? null;
$hasRelease = $release !== null && $release->isUsable();
$version = $hasRelease ? $release->version : null;
$sizeLabel = $hasRelease ? $release->sizeLabel() : null;
$sha256 = $hasRelease ? $release->sha256() : null;
$req = $hasRelease ? $release->requirements : [];
$notes = $hasRelease ? $release->notes : null;
?>
<section class="page-hero download-hero">
    <div class="container">
        <h1>Project Survival</h1>
        <p class="download-tagline">Sobreviva. Explore. Evolua.</p>

        <?php if ($maintenance): ?>
            <div class="site-alert site-alert-info">
                O download está em manutenção no momento. Volte em instantes.
            </div>
        <?php elseif ($hasRelease): ?>
            <a href="<?= e($downloadRoute) ?>" class="btn btn-primary btn-lg download-cta" rel="nofollow">
                Baixar Project Survival
            </a>
            <div class="download-meta">
                <span class="download-meta-item"><strong>Versão:</strong> v<?= e($version) ?></span>
                <span class="download-meta-item"><strong>Plataforma:</strong> Windows</span>
                <?php if ($sizeLabel): ?><span class="download-meta-item"><strong>Tamanho:</strong> <?= e($sizeLabel) ?></span><?php endif; ?>
                <?php if ($channel !== 'stable'): ?><span class="download-meta-item"><strong>Canal:</strong> <?= e($channel) ?></span><?php endif; ?>
            </div>
            <?php if ($stale): ?>
                <p class="muted download-stale">Exibindo as últimas informações conhecidas; o serviço de releases está temporariamente indisponível.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="site-alert site-alert-info">
                Informações de download temporariamente indisponíveis. Tente novamente em instantes.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($hasRelease && $sha256): ?>
<section class="section download-integrity">
    <div class="container">
        <details class="download-hash">
            <summary>Verificação de integridade (SHA-256)</summary>
            <p class="muted">Confira o instalador baixado com o hash oficial abaixo.</p>
            <code class="download-hash-value"><?= e($sha256) ?></code>
        </details>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container download-grid">
        <div class="form-card">
            <h2 class="mb-2">Requisitos</h2>
            <?php if (!empty(array_filter($req))): ?>
                <dl class="account-data">
                    <?php if (!empty($req['os'])): ?><div><dt>Sistema operacional</dt><dd><?= e($req['os']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($req['arch'])): ?><div><dt>Arquitetura</dt><dd><?= e($req['arch']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($req['graphics'])): ?><div><dt>Gráficos</dt><dd><?= e($req['graphics']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($req['internet'])): ?><div><dt>Internet</dt><dd><?= e($req['internet']) ?></dd></div><?php endif; ?>
                </dl>
            <?php else: ?>
                <p class="muted">Requisitos disponíveis em breve.</p>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <h2 class="mb-2">Como instalar</h2>
            <ol class="download-steps">
                <li>Baixe o instalador (<code>ProjectSurvivalSetup.exe</code>).</li>
                <li>Execute o instalador e siga as instruções.</li>
                <li>Abra o launcher do Project Survival.</li>
                <li>O launcher verifica e aplica atualizações automaticamente.</li>
                <li>Clique em <strong>Jogar</strong> e divirta-se.</li>
            </ol>
        </div>
    </div>
</section>

<section class="section download-updates-note">
    <div class="container">
        <div class="form-card">
            <h2 class="mb-2">Atualizações</h2>
            <p>Depois da instalação, o jogo recebe atualizações <strong>automaticamente pelo launcher</strong>.
            Você não precisa baixar o instalador de novo a cada versão — basta abrir o launcher, que verifica,
            baixa e aplica a atualização com segurança.</p>
            <p class="muted" style="font-size:.9rem;">
                O site apenas disponibiliza o download inicial; a atualização é feita pelo launcher/updater oficiais.
            </p>
        </div>
    </div>
</section>

<section class="section download-support">
    <div class="container text-center">
        <h2 class="mb-2">Precisa de ajuda?</h2>
        <a href="<?= e(game_url('support')) ?>" class="btn btn-ghost">Suporte</a>
    </div>
</section>
