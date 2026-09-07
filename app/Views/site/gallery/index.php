<?php
/** @var array $albums */
?>
<section class="page-hero">
    <div class="container">
        <h1>Galeria</h1>
        <p>Screenshots, artes e imagens promocionais.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <?php if (empty($albums)): ?>
            <div class="text-center" style="padding:4rem 0;color:var(--text-muted);">
                <div style="font-size:3rem;opacity:.4;margin-bottom:1rem;">🖼️</div>
                <p>Nenhum álbum disponível ainda.</p>
            </div>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($albums as $album): ?>
                    <a href="/galeria/<?= e($album['slug']) ?>" class="gallery-item reveal">
                        <?php if ($album['cover_image']): ?>
                            <img src="<?= e(uploaded($album['cover_image'])) ?>" alt="<?= e($album['title']) ?>">
                        <?php else: ?>
                            <div class="gallery-placeholder">🖼️</div>
                        <?php endif; ?>
                        <div class="overlay">
                            <div>
                                <?= e($album['title']) ?>
                                <div style="font-size:.8rem;color:var(--text-muted);"><?= (int) $album['items_count'] ?> item(ns)</div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
