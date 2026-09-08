<?php
/**
 * Página de atualizações.
 *
 * @var \App\Services\GameApi\ReleaseInformation|null $release
 * @var array $news
 * @var array $status  ['state'=>,'label'=>,'api_version'=>,...]
 */
$release = $release ?? null;
$news = $news ?? [];
$hasRelease = $release !== null && $release->version !== '';
$statusState = $status['state'] ?? 'disabled';
$statusLabel = $status['label'] ?? 'Indisponível';
$dot = ['online' => '🟢', 'maintenance' => '🟡', 'offline' => '🔴', 'disabled' => '⚪'][$statusState] ?? '⚪';
?>
<section class="page-hero">
    <div class="container">
        <h1>Atualizações</h1>
        <p class="muted">
            <span aria-hidden="true"><?= $dot ?></span> <?= e($statusLabel) ?>
            <?php if ($hasRelease): ?> · Versão atual: <strong>v<?= e($release->version) ?></strong><?php endif; ?>
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($hasRelease && !empty($release->notes)): ?>
            <div class="form-card">
                <h2 class="mb-2">Novidades da versão v<?= e($release->version) ?></h2>
                <?php if (!empty($release->releasedAt)): ?>
                    <p class="muted" style="font-size:.85rem;">Publicada em <?= e(format_date($release->releasedAt, 'd/m/Y')) ?></p>
                <?php endif; ?>
                <div class="prose"><?= nl2br(e($release->notes)) ?></div>
                <a href="/download" class="btn btn-primary mt-2">Baixar a versão mais recente</a>
            </div>
        <?php elseif (!$hasRelease): ?>
            <div class="site-alert site-alert-info">
                Informações de versão temporariamente indisponíveis.
            </div>
        <?php endif; ?>

        <?php if (!empty($news)): ?>
            <div class="form-card mt-3">
                <h2 class="mb-2">Notícias e novidades</h2>
                <ul class="updates-news">
                    <?php foreach ($news as $n): ?>
                        <li>
                            <?php if (!empty($n['published_at'])): ?>
                                <span class="updates-news-date"><?= e(format_date($n['published_at'], 'd/m/Y')) ?></span>
                            <?php endif; ?>
                            <span class="updates-news-title">
                                <?php if (!empty($n['slug'])): ?>
                                    <a href="/noticias/<?= e($n['slug']) ?>"><?= e($n['title'] ?? 'Novidade') ?></a>
                                <?php else: ?>
                                    <?= e($n['title'] ?? 'Novidade') ?>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="/noticias" class="btn btn-ghost mt-2">Ver todas as notícias</a>
            </div>
        <?php endif; ?>

        <div class="form-card mt-3">
            <h2 class="mb-2">Como as atualizações chegam até você</h2>
            <p>Depois de instalar o jogo, o <strong>launcher</strong> cuida das atualizações automaticamente:
            ao abrir, ele verifica se há uma nova versão, baixa, valida a integridade e aplica antes de você jogar.</p>
            <p class="muted" style="font-size:.9rem;">
                O site não distribui patches manuais nem implementa o atualizador — isso é responsabilidade do
                launcher/updater oficiais do Project Survival.
            </p>
        </div>
    </div>
</section>
