<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $category */
/** @var array $categories */
$totalPages = (int) ceil($total / $perPage);
$buildUrl = function (int $p) use ($category) {
    $q = ['page' => $p];
    if ($category !== '') $q['categoria'] = $category;
    return '/noticias?' . http_build_query($q);
};
?>
<section class="page-hero">
    <div class="container">
        <h1>Notícias</h1>
        <p>Acompanhe as últimas novidades e atualizações.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <?php if (!empty($categories)): ?>
        <div class="mb-3" style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a href="/noticias" class="btn <?= $category === '' ? 'btn-primary' : 'btn-ghost' ?>">Todas</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/noticias?categoria=<?= e($cat['slug']) ?>" class="btn <?= $category === $cat['slug'] ? 'btn-primary' : 'btn-ghost' ?>"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="text-center" style="padding:4rem 0;color:var(--text-muted);">
                <div style="font-size:3rem;opacity:.4;margin-bottom:1rem;">📰</div>
                <p>Nenhuma notícia publicada ainda. Volte em breve!</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($items as $news): ?>
                    <article class="news-card reveal">
                        <a href="/noticias/<?= e($news['slug']) ?>" class="news-thumb">
                            <?php if ($news['featured_image']): ?>
                                <img src="<?= e(uploaded($news['featured_image'])) ?>" alt="<?= e($news['title']) ?>">
                            <?php else: ?>
                                <div class="news-thumb-placeholder">📰</div>
                            <?php endif; ?>
                        </a>
                        <div class="news-body">
                            <div class="news-meta"><?= e(format_date($news['published_at'], 'd M Y')) ?></div>
                            <h3><a href="/noticias/<?= e($news['slug']) ?>"><?= e($news['title']) ?></a></h3>
                            <p><?= e($news['excerpt'] ?: excerpt($news['content'], 120)) ?></p>
                            <a href="/noticias/<?= e($news['slug']) ?>" class="news-link">Ler mais →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav class="text-center mt-3" style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= e($buildUrl($i)) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
