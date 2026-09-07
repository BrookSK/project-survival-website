<?php
/** @var array|null $hero */
/** @var array $banners */
/** @var array $sections */
/** @var array $sectionData */
use App\Models\Video;

$siteName = setting('site_name', 'Nome do Jogo');
$tagline = setting('site_tagline', 'A aventura começa aqui');
$description = setting('site_description', 'Website oficial do jogo.');
$discord = setting('social_discord', '');

$hero = $hero ?? null;
$heroTitle = $hero['title'] ?? $siteName;
$heroSubtitle = $hero['subtitle'] ?? $tagline;
$heroImage = !empty($hero['image_path']) ? uploaded($hero['image_path']) : '';
$heroImageMobile = !empty($hero['image_mobile']) ? uploaded($hero['image_mobile']) : '';
$heroLink = $hero['link_url'] ?? ($discord ?: '/sobre');
$heroLinkLabel = $hero['link_label'] ?? 'Conheça o jogo';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg">
        <?php if ($heroImage): ?>
            <picture>
                <?php if ($heroImageMobile): ?><source media="(max-width: 640px)" srcset="<?= e($heroImageMobile) ?>"><?php endif; ?>
                <img src="<?= e($heroImage) ?>" alt="" fetchpriority="high">
            </picture>
        <?php endif; ?>
    </div>
    <div class="hero-grid"></div>
    <div class="container">
        <div class="hero-content reveal">
            <span class="eyebrow">Site Oficial</span>
            <h1><span class="text-gradient"><?= e($heroTitle) ?></span></h1>
            <p class="tagline"><?= e($heroSubtitle) ?></p>
            <p class="description"><?= e($description) ?></p>
            <div class="hero-actions">
                <a href="<?= e($heroLink) ?>" class="btn btn-primary btn-lg"><?= e($heroLinkLabel) ?></a>
                <a href="#trailer" class="btn btn-ghost btn-lg">Ver trailer</a>
            </div>
        </div>
    </div>
</section>

<?php
// Renderiza cada seção conforme o tipo
foreach ($sections as $index => $section):
    $sid = e($section['key']);
    $altBg = $index % 2 === 1 ? ' style="padding-top:0;"' : '';
?>
    <?php if ($section['type'] === 'content'): ?>
        <section class="section" id="<?= $sid ?>">
            <div class="container">
                <div class="grid grid-2" style="align-items:center;gap:3rem;">
                    <div class="reveal">
                        <?php if ($section['subtitle']): ?><span class="eyebrow"><?= e($section['subtitle']) ?></span><?php endif; ?>
                        <h2 class="section-title"><?= e($section['title']) ?></h2>
                        <div class="prose"><?= $section['content'] // sanitizado no salvamento ?></div>
                        <?php if ($section['button_label'] && $section['button_url']): ?>
                            <a href="<?= e($section['button_url']) ?>" class="btn btn-primary mt-3"><?= e($section['button_label']) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($section['image'])): ?>
                        <div class="reveal">
                            <img src="<?= e(uploaded($section['image'])) ?>" alt="<?= e($section['title']) ?>" loading="lazy" style="border-radius:var(--radius);width:100%;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    <?php elseif ($section['type'] === 'features'): ?>
        <section class="section" id="<?= $sid ?>">
            <div class="container">
                <div class="text-center reveal">
                    <?php if ($section['subtitle']): ?><span class="eyebrow"><?= e($section['subtitle']) ?></span><?php endif; ?>
                    <h2 class="section-title"><?= e($section['title']) ?></h2>
                </div>
                <div class="grid grid-3 mt-3">
                    <div class="feature-card reveal"><div class="feature-icon">🌍</div><h3>Universo imersivo</h3><p>Explore um mundo rico em detalhes, construído para envolver você.</p></div>
                    <div class="feature-card reveal"><div class="feature-icon">⚔️</div><h3>Jogabilidade dinâmica</h3><p>Mecânicas pensadas para desafiar e recompensar todos os estilos.</p></div>
                    <div class="feature-card reveal"><div class="feature-icon">🤝</div><h3>Comunidade ativa</h3><p>Faça parte de uma comunidade apaixonada e acompanhe as novidades.</p></div>
                </div>
            </div>
        </section>

    <?php elseif ($section['type'] === 'screenshots' && !empty($sectionData['albums'])): ?>
        <section class="section" id="<?= $sid ?>">
            <div class="container">
                <div class="text-center reveal">
                    <?php if ($section['subtitle']): ?><span class="eyebrow"><?= e($section['subtitle']) ?></span><?php endif; ?>
                    <h2 class="section-title"><?= e($section['title']) ?></h2>
                </div>
                <div class="gallery-grid mt-3">
                    <?php foreach (array_slice($sectionData['albums'], 0, 6) as $album): ?>
                        <a href="/galeria/<?= e($album['slug']) ?>" class="gallery-item reveal">
                            <?php if ($album['cover_image']): ?>
                                <img src="<?= e(uploaded($album['cover_image'])) ?>" alt="<?= e($album['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="gallery-placeholder">🖼️</div>
                            <?php endif; ?>
                            <div class="overlay"><div><?= e($album['title']) ?></div></div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3"><a href="/galeria" class="btn btn-ghost">Ver galeria completa</a></div>
            </div>
        </section>

    <?php elseif ($section['type'] === 'trailer' && !empty($sectionData['trailer'])): $tr = $sectionData['trailer']; ?>
        <section class="section" id="trailer">
            <div class="container">
                <div class="text-center reveal">
                    <?php if ($section['subtitle']): ?><span class="eyebrow"><?= e($section['subtitle']) ?></span><?php endif; ?>
                    <h2 class="section-title"><?= e($section['title']) ?></h2>
                </div>
                <div class="video-embed reveal mt-3">
                    <iframe src="<?= e(Video::embedUrl($tr)) ?>" title="<?= e($tr['title']) ?>"
                            loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </section>

    <?php elseif ($section['type'] === 'news' && !empty($sectionData['news'])): ?>
        <section class="section" id="<?= $sid ?>">
            <div class="container">
                <div class="flex items-center justify-between reveal" style="display:flex;align-items:end;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
                    <div>
                        <?php if ($section['subtitle']): ?><span class="eyebrow"><?= e($section['subtitle']) ?></span><?php endif; ?>
                        <h2 class="section-title" style="margin-bottom:0;"><?= e($section['title']) ?></h2>
                    </div>
                    <a href="<?= e($section['button_url'] ?: '/noticias') ?>" class="btn btn-ghost"><?= e($section['button_label'] ?: 'Ver todas') ?></a>
                </div>
                <div class="news-grid">
                    <?php foreach ($sectionData['news'] as $news): ?>
                        <article class="news-card reveal">
                            <a href="/noticias/<?= e($news['slug']) ?>" class="news-thumb">
                                <?php if ($news['featured_image']): ?>
                                    <img src="<?= e(uploaded($news['featured_image'])) ?>" alt="<?= e($news['title']) ?>" loading="lazy">
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
            </div>
        </section>

    <?php elseif ($section['type'] === 'faq' && !empty($sectionData['faqs'])): ?>
        <section class="section" id="<?= $sid ?>">
            <div class="container" style="max-width:820px;">
                <div class="text-center reveal">
                    <?php if ($section['subtitle']): ?><span class="eyebrow"><?= e($section['subtitle']) ?></span><?php endif; ?>
                    <h2 class="section-title"><?= e($section['title']) ?></h2>
                </div>
                <div class="mt-3">
                    <?php foreach (array_slice($sectionData['faqs'], 0, 1) as $groupName => $faqs): ?>
                        <?php foreach (array_slice($faqs, 0, 5) as $faq): ?>
                            <div class="faq-item">
                                <button class="faq-question" aria-expanded="false">
                                    <span><?= e($faq['question']) ?></span><span class="chev">▾</span>
                                </button>
                                <div class="faq-answer"><div class="faq-answer-inner"><?= $faq['answer'] ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3"><a href="<?= e($section['button_url'] ?: '/faq') ?>" class="btn btn-ghost"><?= e($section['button_label'] ?: 'Ver todas as perguntas') ?></a></div>
            </div>
        </section>

    <?php elseif ($section['type'] === 'cta'): ?>
        <section class="section" id="<?= $sid ?>">
            <div class="container">
                <div class="cta-band reveal">
                    <h2><?= e($section['title']) ?></h2>
                    <?php if ($section['content']): ?><div><?= $section['content'] ?></div><?php endif; ?>
                    <?php
                    $ctaUrl = $section['button_url'] ?: ($discord ?: '/contato');
                    $ctaLabel = $section['button_label'] ?: 'Fale conosco';
                    ?>
                    <a href="<?= e($ctaUrl) ?>" <?= $discord && !$section['button_url'] ? 'target="_blank" rel="noopener"' : '' ?> class="btn btn-primary btn-lg mt-3"><?= e($ctaLabel) ?></a>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>
