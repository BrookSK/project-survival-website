<?php
/**
 * Layout do site público.
 *
 * @var string $content  Conteúdo da página.
 * @var array  $seo      Metadados de SEO (SeoService::build), opcional.
 */
use App\Models\Menu;
use App\Models\SocialLink;
use App\Services\SeoService;

$seo = $seo ?? SeoService::build();
$siteName = setting('site_name', 'Nome do Jogo');
$flashes = \App\Core\Session::getFlashes();
$currentUri = (new \App\Core\Request())->uri();

// Menus dinâmicos
$menuModel = new Menu();
$headerItems = $menuModel->itemsByLocation('header');
$footerItems = $menuModel->itemsByLocation('footer');

// Redes sociais: preferencialmente da tabela social_links; fallback para settings antigas
$socialLinks = (new SocialLink())->activeOrdered();
if (empty($socialLinks)) {
    foreach (['discord', 'youtube', 'instagram', 'x' => 'twitter', 'steam'] as $key => $legacy) {
        $platform = is_int($key) ? $legacy : $key;
        $val = setting('social_' . $legacy);
        if ($val) {
            $socialLinks[] = ['platform' => $platform, 'label' => ucfirst($platform), 'url' => $val, 'icon' => $platform];
        }
    }
}

// URL do Discord para CTAs (procura na lista de redes sociais)
$discord = '';
foreach ($socialLinks as $sl) {
    if (($sl['platform'] ?? '') === 'discord') { $discord = $sl['url']; break; }
}
if ($discord === '') {
    $discord = (string) setting('social_discord', '');
}

$logo = setting('site_logo', '');
$favicon = setting('site_favicon', '');
$cookieEnabled = (bool) setting('cookie_enabled', false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seo['title']) ?></title>
    <meta name="description" content="<?= e($seo['description']) ?>">
    <?php if (!empty($seo['keywords'])): ?><meta name="keywords" content="<?= e($seo['keywords']) ?>"><?php endif; ?>
    <meta name="robots" content="<?= e($seo['robots']) ?>">
    <link rel="canonical" href="<?= e($seo['canonical']) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= e($seo['type']) ?>">
    <meta property="og:site_name" content="<?= e($seo['site_name']) ?>">
    <meta property="og:title" content="<?= e($seo['title']) ?>">
    <meta property="og:description" content="<?= e($seo['description']) ?>">
    <meta property="og:url" content="<?= e($seo['canonical']) ?>">
    <?php if (!empty($seo['image'])): ?><meta property="og:image" content="<?= e($seo['image']) ?>"><?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <?php if (!empty($seo['twitter'])): ?><meta name="twitter:site" content="<?= e($seo['twitter']) ?>"><?php endif; ?>
    <meta name="twitter:title" content="<?= e($seo['title']) ?>">
    <meta name="twitter:description" content="<?= e($seo['description']) ?>">
    <?php if (!empty($seo['image'])): ?><meta name="twitter:image" content="<?= e($seo['image']) ?>"><?php endif; ?>

    <!-- Dados estruturados (JSON-LD) -->
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => $seo['site_name'],
        'url'      => url(),
        'description' => setting('site_description', ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>

    <?php if (!empty($favicon)): ?><link rel="icon" href="<?= e(uploaded($favicon)) ?>"><?php endif; ?>
    <?php if ($gv = setting('seo_google_verification')): ?><meta name="google-site-verification" content="<?= e($gv) ?>"><?php endif; ?>

    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Pular para o conteúdo</a>

<header class="site-header" id="siteHeader">
    <div class="container">
        <a href="/" class="brand">
            <?php if (!empty($logo)): ?>
                <img src="<?= e(uploaded($logo)) ?>" alt="<?= e($siteName) ?>" style="height:36px;width:auto;">
            <?php else: ?>
                <span class="brand-logo"><?= e(str_sub($siteName, 0, 1)) ?></span>
                <span><?= e($siteName) ?></span>
            <?php endif; ?>
        </a>
        <nav class="main-nav" aria-label="Navegação principal">
            <?php foreach ($headerItems as $item): ?>
                <a href="<?= e($item['url']) ?>" target="<?= e($item['target']) ?>"
                   class="<?= $currentUri === $item['url'] ? 'active' : '' ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-cta">
            <?php if ($discord): ?>
                <a href="<?= e($discord) ?>" target="_blank" rel="noopener" class="btn btn-primary">Comunidade</a>
            <?php endif; ?>
            <button class="nav-toggle" id="navToggle" aria-label="Abrir menu" aria-expanded="false">&#9776;</button>
        </div>
    </div>
</header>

<nav class="mobile-nav" id="mobileNav" aria-label="Menu móvel">
    <?php foreach ($headerItems as $item): ?>
        <a href="<?= e($item['url']) ?>" target="<?= e($item['target']) ?>"><?= e($item['label']) ?></a>
    <?php endforeach; ?>
    <?php if ($discord): ?>
        <a href="<?= e($discord) ?>" target="_blank" rel="noopener" class="btn btn-primary mt-3">Entrar na comunidade</a>
    <?php endif; ?>
</nav>

<main id="main" tabindex="-1">
    <?php foreach (($flashes['success'] ?? []) as $msg): ?>
        <div class="container mt-3"><div class="site-alert site-alert-success"><?= e($msg) ?></div></div>
    <?php endforeach; ?>
    <?php foreach (($flashes['error'] ?? []) as $msg): ?>
        <div class="container mt-3"><div class="site-alert site-alert-error"><?= e($msg) ?></div></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="/" class="brand">
                    <?php if (!empty($logo)): ?>
                        <img src="<?= e(uploaded($logo)) ?>" alt="<?= e($siteName) ?>" style="height:34px;width:auto;">
                    <?php else: ?>
                        <span class="brand-logo"><?= e(str_sub($siteName, 0, 1)) ?></span>
                        <span><?= e($siteName) ?></span>
                    <?php endif; ?>
                </a>
                <p><?= e(setting('site_description', 'Website oficial do jogo.')) ?></p>
                <div class="social-links">
                    <?php foreach ($socialLinks as $sl): ?>
                        <a href="<?= e($sl['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($sl['label']) ?>" title="<?= e($sl['label']) ?>"><?= e(str_sub($sl['label'], 0, 2)) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="footer-col">
                <h4>Navegação</h4>
                <?php foreach ($headerItems as $item): ?>
                    <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer-col">
                <h4>Legal</h4>
                <?php foreach ($footerItems as $item): ?>
                    <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="footer-bottom">
            <span><?= e(setting('copyright_text') ?: ('© ' . date('Y') . ' ' . $siteName . '. Todos os direitos reservados.')) ?></span>
            <span>Feito com paixão pela comunidade.</span>
        </div>
    </div>
</footer>

<?php if ($cookieEnabled): ?>
<div class="cookie-banner" id="cookieBanner" role="dialog" aria-label="Aviso de cookies" hidden>
    <p>
        <?= e(setting('cookie_text', 'Utilizamos cookies para melhorar sua experiência.')) ?>
        <?php if ($cp = setting('cookie_policy_url')): ?>
            <a href="<?= e($cp) ?>">Saiba mais</a>.
        <?php endif; ?>
    </p>
    <div class="actions">
        <button type="button" class="btn btn-primary" id="cookieAccept">Aceitar</button>
    </div>
</div>
<script>
(function () {
    var b = document.getElementById('cookieBanner');
    if (!b) return;
    try {
        if (localStorage.getItem('cookie_consent') === '1') return;
    } catch (e) {}
    b.hidden = false;
    document.getElementById('cookieAccept').addEventListener('click', function () {
        try { localStorage.setItem('cookie_consent', '1'); } catch (e) {}
        b.hidden = true;
    });
})();
</script>
<?php endif; ?>

<script src="<?= e(asset('js/site.js')) ?>"></script>
</body>
</html>
