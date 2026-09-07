<?php
/** @var array $stats */
/** @var array $recentMessages */
/** @var array $recentAudit */
/** @var array $newsChart */
$statusLabels = [
    'new' => ['Novo', 'badge-info'], 'read' => ['Lido', 'badge-muted'],
    'replied' => ['Respondido', 'badge-success'], 'archived' => ['Arquivado', 'badge-muted'],
];
$maxChart = max(1, max($newsChart ?: [1]));
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral do conteúdo e da atividade do site.</p>
    </div>
</div>

<!-- Atalhos rápidos -->
<div class="quick-actions mb-2">
    <?php
    $shortcuts = [
        ['news.create', '/admin/noticias/criar', '📰 Nova notícia'],
        ['pages.create', '/admin/paginas/criar', '📄 Nova página'],
        ['faq.create', '/admin/faq/criar', '❓ Nova FAQ'],
        ['gallery.create', '/admin/galeria/criar', '🖼️ Novo álbum'],
        ['media.upload', '/admin/midia', '🗂️ Upload de mídia'],
        ['settings.view', '/admin/configuracoes', '⚙️ Configurações'],
    ];
    foreach ($shortcuts as [$perm, $url, $label]):
        if (!has_permission($perm)) continue; ?>
        <a href="<?= e($url) ?>" class="quick-action"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="stats-grid">
    <?php
    $cards = [
        ['📰', $stats['news_published'], 'Notícias publicadas', '/admin/noticias?status=published', 'news.view'],
        ['📝', $stats['news_draft'], 'Rascunhos', '/admin/noticias?status=draft', 'news.view'],
        ['📄', $stats['pages'], 'Páginas', '/admin/paginas', 'pages.view'],
        ['🗂️', $stats['media'], 'Arquivos de mídia', '/admin/midia', 'media.view'],
        ['✉️', $stats['messages'], 'Mensagens novas', '/admin/mensagens', 'messages.view'],
        ['👤', $stats['users'], 'Usuários', '/admin/usuarios', 'users.view'],
    ];
    foreach ($cards as [$icon, $value, $label, $url, $perm]):
        if (!has_permission($perm)) continue; ?>
        <a href="<?= e($url) ?>" class="stat-card" style="text-decoration:none;color:inherit;">
            <div class="stat-icon"><?= $icon ?></div>
            <div>
                <div class="stat-value"><?= (int) $value ?></div>
                <div class="stat-label"><?= e($label) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<div class="grid-2 mt-2">
    <?php if (has_permission('news.view')): ?>
    <div class="card">
        <div class="card-title">Notícias publicadas (6 meses)</div>
        <div class="mini-chart">
            <?php foreach ($newsChart as $ym => $total):
                $h = (int) round(($total / $maxChart) * 100); ?>
                <div class="mini-bar-wrap" title="<?= e($ym) ?>: <?= (int) $total ?>">
                    <div class="mini-bar" style="height:<?= max(4, $h) ?>%;"></div>
                    <span class="mini-label"><?= e(substr($ym, 5, 2)) ?>/<?= e(substr($ym, 2, 2)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('messages.view')): ?>
    <div class="card">
        <div class="flex items-center justify-between mb-2">
            <div class="card-title" style="margin:0;">Mensagens recentes</div>
            <a href="/admin/mensagens" class="text-sm">Ver todas</a>
        </div>
        <?php if (empty($recentMessages)): ?>
            <p class="muted text-sm">Nenhuma mensagem recebida.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                    <?php foreach ($recentMessages as $m):
                        [$label, $badge] = $statusLabels[$m['status']] ?? ['—', 'badge-muted']; ?>
                        <tr>
                            <td><a href="/admin/mensagens/<?= (int) $m['id'] ?>"><?= e($m['subject']) ?></a><div class="muted text-sm"><?= e($m['name']) ?></div></td>
                            <td><span class="badge <?= $badge ?>"><?= e($label) ?></span></td>
                            <td class="muted text-sm"><?= e(format_date($m['created_at'], 'd/m H:i')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (has_permission('audit.view') && !empty($recentAudit)): ?>
<div class="card">
    <div class="flex items-center justify-between mb-2">
        <div class="card-title" style="margin:0;">Atividade recente</div>
        <a href="/admin/auditoria" class="text-sm">Ver tudo</a>
    </div>
    <ul style="list-style:none;display:flex;flex-direction:column;gap:.75rem;">
        <?php foreach ($recentAudit as $a): ?>
            <li class="text-sm">
                <strong><?= e($a['user_name'] ?? 'Sistema') ?></strong>
                <span class="muted">— <?= e($a['description'] ?: ($a['action'] . ' em ' . $a['module'])) ?></span>
                <div class="muted" style="font-size:.78rem;"><?= e(format_date($a['created_at'])) ?></div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
