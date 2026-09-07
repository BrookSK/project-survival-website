<?php
/**
 * @var string $title
 * @var string|null $version
 * @var string|null $effectiveAt
 * @var string|null $content   HTML já sanitizado no salvamento
 * @var string $contactEmail
 */
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($title) ?></h1>
        <?php if ($version): ?>
            <p>Versão <?= e($version) ?><?php if ($effectiveAt): ?> · vigente desde <?= e(format_date($effectiveAt, 'd/m/Y')) ?><?php endif; ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section" style="padding-top:1.5rem;">
    <div class="container" style="max-width:820px;">
        <article class="legal-page prose">
            <?php if ($content !== null && trim($content) !== ''): ?>
                <?= $content ?>
            <?php else: ?>
                <div class="site-alert site-alert-info">
                    Este documento ainda não foi publicado. Volte em breve.
                </div>
            <?php endif; ?>
        </article>

        <?php if ($contactEmail !== ''): ?>
            <p class="muted mt-3" style="font-size:.9rem;">
                Dúvidas sobre privacidade? Fale com o responsável: <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>.
            </p>
        <?php endif; ?>
    </div>
</section>
