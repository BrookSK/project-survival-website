<?php
/** @var array $news */
/** @var array $categories */
/** @var array $related */
?>
<section class="page-hero" style="text-align:left;">
    <div class="container" style="max-width:820px;">
        <div class="news-meta" style="margin-bottom:1rem;">
            <?= e(format_date($news['published_at'], 'd \d\e F \d\e Y')) ?>
            <?php foreach ($categories as $c): ?>
                · <a href="/noticias?categoria=<?= e($c['slug']) ?>" style="color:var(--neon);"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <h1><?= e($news['title']) ?></h1>
    </div>
</section>

<article class="section" style="padding-top:1rem;">
    <div class="container" style="max-width:820px;">
        <?php if ($news['featured_image']): ?>
            <img src="<?= e(uploaded($news['featured_image'])) ?>" alt="<?= e($news['title']) ?>" style="width:100%;border-radius:var(--radius);margin-bottom:2rem;">
        <?php endif; ?>
        <div class="prose" style="max-width:none;">
            <?= $news['content'] // HTML gerenciado no painel ?>
        </div>
    </div>
</article>

<?php if (!empty($related)): ?>
<section class="section" style="padding-top:0;">
    <div class="container">
        <h2 class="section-title mb-3">Continue lendo</h2>
        <div class="news-grid">
            <?php foreach ($related as $r): if ($r['id'] === $news['id']) continue; ?>
                <article class="news-card">
                    <a href="/noticias/<?= e($r['slug']) ?>" class="news-thumb">
                        <?php if ($r['featured_image']): ?>
                            <img src="<?= e(uploaded($r['featured_image'])) ?>" alt="<?= e($r['title']) ?>">
                        <?php else: ?>
                            <div class="news-thumb-placeholder">📰</div>
                        <?php endif; ?>
                    </a>
                    <div class="news-body">
                        <div class="news-meta"><?= e(format_date($r['published_at'], 'd M Y')) ?></div>
                        <h3><a href="/noticias/<?= e($r['slug']) ?>"><?= e($r['title']) ?></a></h3>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
