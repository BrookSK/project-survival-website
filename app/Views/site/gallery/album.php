<?php
/** @var array $album */
/** @var array $items */
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($album['title']) ?></h1>
        <?php if ($album['description']): ?><p><?= e($album['description']) ?></p><?php endif; ?>
        <div class="mt-3"><a href="/galeria" class="btn btn-ghost">← Voltar à galeria</a></div>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <?php if (empty($items)): ?>
            <p class="text-center" style="color:var(--text-muted);padding:3rem 0;">Este álbum ainda não possui itens.</p>
        <?php else: ?>
            <div class="gallery-grid" data-lightbox-gallery>
                <?php foreach ($items as $item): ?>
                    <?php if ($item['type'] === 'video' && $item['video_url']): ?>
                        <a href="<?= e($item['video_url']) ?>" target="_blank" rel="noopener" class="gallery-item">
                            <div class="gallery-placeholder">▶️</div>
                            <div class="overlay"><?= e($item['title'] ?: 'Assistir vídeo') ?></div>
                        </a>
                    <?php elseif ($item['file_path']): $full = uploaded($item['file_path']); ?>
                        <a href="<?= e($full) ?>" class="gallery-item" data-lightbox="<?= e($full) ?>" data-caption="<?= e($item['title']) ?>">
                            <img src="<?= e($full) ?>" alt="<?= e($item['alt_text'] ?: $item['title']) ?>" loading="lazy">
                            <?php if ($item['title']): ?><div class="overlay"><?= e($item['title']) ?></div><?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Lightbox -->
            <div class="lightbox" id="lightbox" aria-hidden="true">
                <button class="lightbox-close" aria-label="Fechar">×</button>
                <button class="lightbox-nav lightbox-prev" aria-label="Anterior">‹</button>
                <figure class="lightbox-figure">
                    <img id="lightboxImg" src="" alt="">
                    <figcaption id="lightboxCaption"></figcaption>
                </figure>
                <button class="lightbox-nav lightbox-next" aria-label="Próxima">›</button>
            </div>
            <script src="<?= e(asset('js/lightbox.js')) ?>"></script>
        <?php endif; ?>
    </div>
</section>
