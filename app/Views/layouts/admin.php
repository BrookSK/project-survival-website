<?php
/**
 * Layout do painel administrativo.
 *
 * Variáveis esperadas:
 * @var string $content   Conteúdo renderizado da view.
 * @var string $title     Título da página.
 * @var array  $breadcrumbs  [ ['label'=>..., 'url'=>...], ... ]  (opcional)
 */
$title = $title ?? 'Painel';
$breadcrumbs = $breadcrumbs ?? [];
$user = auth_user() ?? [];
$siteName = setting('site_name', 'Painel');
$flashes = \App\Core\Session::getFlashes();
$uri = (new \App\Core\Request())->uri();

/**
 * Helper local: marca link ativo comparando o início da URI.
 */
$isActive = function (string $prefix) use ($uri): string {
    if ($prefix === '/admin') {
        return $uri === '/admin' ? 'active' : '';
    }
    return strpos($uri, $prefix) === 0 ? 'active' : '';
};

// Itens de navegação: [permissão, url, rótulo, ícone]
$navContent = [
    ['home.view',       '/admin/home',       'Home',       '🏠'],
    ['pages.view',      '/admin/paginas',    'Páginas',    '📄'],
    ['news.view',       '/admin/noticias',   'Notícias',   '📰'],
    ['categories.view', '/admin/categorias', 'Categorias', '🏷️'],
    ['faq.view',        '/admin/faq',        'FAQ',        '❓'],
    ['gallery.view',    '/admin/galeria',    'Galeria',    '🖼️'],
    ['videos.view',     '/admin/videos',     'Vídeos',     '🎞️'],
    ['media.view',      '/admin/midia',      'Mídia',      '🗂️'],
    ['banners.view',    '/admin/banners',    'Banners',    '🎬'],
    ['menus.view',      '/admin/menus',      'Menus',      '🧭'],
];
$navComm = [
    ['messages.view', '/admin/mensagens', 'Mensagens', '✉️'],
];
$navSystem = [
    ['social.view',   '/admin/redes-sociais', 'Redes sociais', '🔗'],
    ['users.view',    '/admin/usuarios',      'Usuários',      '👤'],
    ['roles.view',    '/admin/perfis',        'Perfis',        '🛡️'],
    ['email_templates.view', '/admin/email-templates', 'E-mails', '✉️'],
    ['redirects.view','/admin/redirects',     'Redirects',     '↪️'],
    ['settings.view', '/admin/configuracoes', 'Configurações', '⚙️'],
    ['audit.view',    '/admin/auditoria',     'Auditoria',     '📋'],
    ['system.view',   '/admin/sistema',       'Sistema',       '🖥️'],
];

$renderNav = function (array $items) use ($isActive) {
    foreach ($items as [$perm, $url, $label, $icon]) {
        if (!has_permission($perm)) {
            continue;
        }
        $active = $isActive($url);
        echo '<a class="nav-link ' . $active . '" href="' . e($url) . '">';
        echo '<span class="icon">' . $icon . '</span><span>' . e($label) . '</span></a>';
    }
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · <?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="logo"><?= e(str_sub($siteName, 0, 1)) ?></span>
            <span><?= e($siteName) ?></span>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-link <?= $isActive('/admin') ?>" href="/admin">
                <span class="icon">📊</span><span>Dashboard</span>
            </a>

            <div class="nav-group-title">Conteúdo</div>
            <?php $renderNav($navContent); ?>

            <div class="nav-group-title">Comunicação</div>
            <?php $renderNav($navComm); ?>

            <div class="nav-group-title">Sistema</div>
            <?php $renderNav($navSystem); ?>
        </nav>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">☰</button>
                <nav class="breadcrumbs" aria-label="Trilha de navegação">
                    <a href="/admin">Início</a>
                    <?php foreach ($breadcrumbs as $i => $crumb): ?>
                        <span class="sep">/</span>
                        <?php if (!empty($crumb['url']) && $i < count($breadcrumbs) - 1): ?>
                            <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                        <?php else: ?>
                            <span class="current"><?= e($crumb['label']) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </div>
            <div class="topbar-right">
            <?php $notifCount = \App\Services\NotificationService::unreadCount(); ?>
            <a href="/admin/notificacoes" class="notif-bell" aria-label="Notificações" title="Notificações">
                🔔<?php if ($notifCount > 0): ?><span class="notif-badge"><?= $notifCount > 99 ? '99+' : $notifCount ?></span><?php endif; ?>
            </a>
            <div class="topbar-user">
                <button class="user-btn" id="userBtn">
                    <span class="user-avatar"><?= e(str_sub($user['name'] ?? '?', 0, 1)) ?></span>
                    <span><?= e($user['name'] ?? 'Usuário') ?></span>
                </button>
                <div class="user-menu" id="userMenu">
                    <a href="/admin/perfil">Meu perfil</a>
                    <a href="/" target="_blank">Ver site</a>
                    <form method="post" action="/admin/logout">
                        <?= csrf_field() ?>
                        <button type="submit">Sair</button>
                    </form>
                </div>
            </div>
            </div>
        </header>

        <main class="content" id="main" tabindex="-1">
            <?= $content ?>
        </main>
    </div>
</div>

<!-- Flash messages -->
<div class="flash-stack">
    <?php foreach (($flashes['success'] ?? []) as $msg): ?>
        <div class="alert alert-success" data-auto-dismiss>
            <span><?= e($msg) ?></span><button class="alert-close" aria-label="Fechar">×</button>
        </div>
    <?php endforeach; ?>
    <?php foreach (($flashes['error'] ?? []) as $msg): ?>
        <div class="alert alert-error" data-auto-dismiss>
            <span><?= e($msg) ?></span><button class="alert-close" aria-label="Fechar">×</button>
        </div>
    <?php endforeach; ?>
    <?php foreach (($flashes['info'] ?? []) as $msg): ?>
        <div class="alert alert-info" data-auto-dismiss>
            <span><?= e($msg) ?></span><button class="alert-close" aria-label="Fechar">×</button>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal de confirmação global -->
<div class="modal-backdrop" id="confirmModal">
    <div class="modal" role="dialog" aria-modal="true">
        <h3>Confirmar ação</h3>
        <p id="confirmText">Tem certeza?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="confirmCancel" type="button">Cancelar</button>
            <button class="btn btn-danger" id="confirmOk" type="button">Confirmar</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('js/admin.js')) ?>"></script>
<script src="<?= e(asset('js/editor.js')) ?>"></script>
<script src="<?= e(asset('js/sortable.js')) ?>"></script>
</body>
</html>
