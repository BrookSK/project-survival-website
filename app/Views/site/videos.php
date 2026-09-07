<?php
/** @var array $videos */
use App\Models\Video;
?>
<section class="page-hero">
    <div class="container">
        <h1>Vídeos</h1>
        <p>Trailers e vídeos oficiais.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <?php if (empty($videos)): ?>
            <div class="text-center" style="padding:4rem 0;color:var(--text-muted);">
                <div style="font-size:3rem;opacity:.4;margin-bottom:1rem;">🎞️</div>
                <p>Nenhum vídeo disponível ainda.</p>
            </div>
        <?php else: ?>
            <?php $first = $videos[0]; ?>
            <div class="video-embed reveal mb-3">
                <iframe src="<?= e(Video::embedUrl($first)) ?>" title="<?= e($first['title']) ?>" loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <h2 class="text-center mb-3" style="font-size:1.4rem;"><?= e($first['title']) ?></h2>

            <?php if (count($videos) > 1): ?>
                <div class="news-grid mt-3">
                    <?php foreach (array_slice($videos, 1) as $v): ?>
                        <article class="news-card reveal">
                            <a href="<?= e($v['url']) ?>" target="_blank" rel="noopener" class="news-thumb">
                                <?php if ($v['thumbnail']): ?>
                                    <img src="<?= e($v['thumbnail']) ?>" alt="<?= e($v['title']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="news-thumb-placeholder">▶️</div>
                                <?php endif; ?>
                            </a>
                            <div class="news-body">
                                <h3><a href="<?= e($v['url']) ?>" target="_blank" rel="noopener"><?= e($v['title']) ?></a></h3>
                                <?php if ($v['description']): ?><p><?= e(excerpt($v['description'], 100)) ?></p><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
