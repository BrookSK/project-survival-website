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

// SEO robots: força noindex em áreas privadas (conta/login/cadastro),
// independentemente do que o controller definiu.
$privatePrefixes = ['/conta', '/login', '/criar-conta'];
$isPrivateArea = false;
foreach ($privatePrefixes as $pfx) {
    if ($currentUri === $pfx || strpos($currentUri, $pfx . '/') === 0) { $isPrivateArea = true; break; }
}
$robotsMeta = $isPrivateArea ? 'noindex, nofollow' : ($seo['robots'] ?? 'index,follow');

// Estado do jogador (integração com a API do jogo). Não expõe tokens.
$playerLoggedIn = \App\Services\GameApi\PlayerSession::check();
$playerName = $playerLoggedIn ? \App\Services\GameApi\PlayerSession::displayName() : '';
$gameApiOn = \App\Services\GameApi\GameApiConfig::isEnabled();

// Reaceite de documentos obrigatórios (quando há nova versão publicada).
$reacceptPending = [];
if ($playerLoggedIn) {
    $pid = (string) (\App\Services\GameApi\PlayerSession::userId() ?? '');
    if ($pid !== '') {
        try {
            $reacceptPending = (new \App\Services\ConsentService())->pendingReacceptance($pid);
        } catch (\Throwable $e) {
            $reacceptPending = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seo['title']) ?></title>
    <meta name="description" content="<?= e($seo['description']) ?>">
    <?php if (!empty($seo['keywords'])): ?><meta name="keywords" content="<?= e($seo['keywords']) ?>"><?php endif; ?>
    <meta name="robots" content="<?= e($robotsMeta) ?>">
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
            <a href="/download" class="<?= (strpos($currentUri, '/download') === 0) ? 'active' : '' ?>">Baixar</a>
            <a href="/updates" class="<?= (strpos($currentUri, '/updates') === 0) ? 'active' : '' ?>">Atualizações</a>
        </nav>
        <div class="header-cta">
            <?php if ($gameApiOn): ?>
                <?php if ($playerLoggedIn): ?>
                    <div class="account-menu" id="accountMenu">
                        <button class="account-btn" id="accountBtn" aria-haspopup="true" aria-expanded="false">
                            <span class="account-avatar"><?= e(str_sub($playerName, 0, 1)) ?></span>
                            <span class="account-name"><?= e($playerName) ?></span>
                        </button>
                        <div class="account-dropdown" id="accountDropdown">
                            <a href="/conta">Minha conta</a>
                            <a href="/conta/inventario">Inventário</a>
                            <a href="/conta/resgatar">Resgatar código</a>
                            <a href="/loja">Loja</a>
                            <form method="post" action="/logout">
                                <?= csrf_field() ?>
                                <button type="submit">Sair</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login" class="btn btn-ghost">Entrar</a>
                    <a href="/criar-conta" class="btn btn-primary">Criar conta</a>
                <?php endif; ?>
            <?php elseif ($discord): ?>
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
    <a href="/download">Baixar</a>
    <a href="/updates">Atualizações</a>
    <?php if ($gameApiOn): ?>
        <a href="/loja">Loja</a>
        <?php if ($playerLoggedIn): ?>
            <a href="/conta">Minha conta</a>
            <a href="/conta/inventario">Inventário</a>
            <a href="/conta/resgatar">Resgatar código</a>
            <form method="post" action="/logout" class="mt-2">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost" style="width:100%;">Sair</button>
            </form>
        <?php else: ?>
            <a href="/login">Entrar</a>
            <a href="/criar-conta" class="btn btn-primary mt-2">Criar conta</a>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($discord): ?>
        <a href="<?= e($discord) ?>" target="_blank" rel="noopener" class="btn btn-primary mt-3">Entrar na comunidade</a>
    <?php endif; ?>
</nav>

<?php if (!empty($reacceptPending)): ?>
<div class="reaccept-banner">
    <div class="container">
        Atualizamos nossos documentos. Revise e confirme:
        <?php foreach ($reacceptPending as $i => $t): ?>
            <?= $i > 0 ? ' · ' : ' ' ?><a href="/<?= $t === 'terms' ? 'termos' : 'privacidade' ?>"><?= $t === 'terms' ? 'Termos de Uso' : 'Política de Privacidade' ?></a>
        <?php endforeach; ?>
        · <a href="/conta/privacidade">gerenciar consentimentos</a>.
    </div>
</div>
<?php endif; ?>

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
                <a href="/privacidade">Privacidade</a>
                <a href="/termos">Termos de Uso</a>
                <a href="/termos-de-compra">Termos de Compra</a>
                <a href="/reembolso">Reembolso</a>
                <a href="/cookies">Cookies</a>
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
<div class="cookie-banner" id="cookieBanner" role="dialog" aria-label="Aviso de cookies" aria-live="polite" hidden>
    <p>
        <?= e(setting('cookie_text', 'Utilizamos cookies essenciais para o funcionamento do site. Você pode escolher aceitar cookies opcionais.')) ?>
        <a href="/cookies">Política de Cookies</a>.
    </p>
    <div class="actions">
        <button type="button" class="btn btn-primary" data-cookie-choice="accepted">Aceitar</button>
        <button type="button" class="btn btn-ghost" data-cookie-choice="rejected">Recusar opcionais</button>
        <a href="/privacidade/cookies" class="btn btn-ghost">Configurar</a>
    </div>
</div>
<?php endif; ?>

<script src="<?= e(asset('js/site.js')) ?>"></script>
</body>
</html>
