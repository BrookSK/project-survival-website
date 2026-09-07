<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
/** @var string $status */
/** @var string $sort */
/** @var string $dir */
$statusBadge = [
    'published' => ['Publicada', 'badge-success'],
    'scheduled' => ['Agendada', 'badge-info'],
    'draft'     => ['Rascunho', 'badge-muted'],
    'archived'  => ['Arquivada', 'badge-warning'],
];
$sortLink = function (string $col, string $label) use ($sort, $dir, $search, $status) {
    $newDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $col ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    $q = http_build_query(['q' => $search, 'status' => $status, 'sort' => $col, 'dir' => $newDir]);
    return '<a href="/admin/noticias?' . e($q) . '" style="color:inherit;">' . e($label) . $arrow . '</a>';
};
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Notícias</h1>
        <p class="page-subtitle"><?= (int) $total ?> notícia(s).</p>
    </div>
    <?php if (has_permission('news.create')): ?>
        <a href="/admin/noticias/criar" class="btn btn-primary">+ Nova notícia</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/noticias">
            <input type="text" name="q" class="search" placeholder="Buscar por título..." value="<?= e($search) ?>">
            <select name="status">
                <option value="">Todos os status</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicadas</option>
                <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Agendadas</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Rascunhos</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arquivadas</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon">📰</div>
            <p>Nenhuma notícia encontrada.</p>
            <?php if (has_permission('news.create')): ?>
                <a href="/admin/noticias/criar" class="btn btn-primary">Criar a primeira notícia</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <form method="post" action="/admin/noticias/bulk" id="bulkForm">
            <?= csrf_field() ?>
            <?php if (has_permission('news.edit')): ?>
            <div class="toolbar" style="justify-content:flex-start;">
                <select name="bulk_action" style="max-width:200px;">
                    <option value="">Ações em massa…</option>
                    <option value="publish">Publicar</option>
                    <option value="archive">Arquivar</option>
                    <?php if (has_permission('news.delete')): ?><option value="delete">Excluir</option><?php endif; ?>
                </select>
                <button type="button" class="btn btn-secondary" onclick="submitBulk()">Aplicar</button>
            </div>
            <?php endif; ?>

            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <?php if (has_permission('news.edit')): ?><th style="width:34px;"><input type="checkbox" onclick="toggleAll(this)"></th><?php endif; ?>
                            <th><?= $sortLink('title', 'Título') ?></th>
                            <th><?= $sortLink('status', 'Status') ?></th>
                            <th><?= $sortLink('views', 'Views') ?></th>
                            <th><?= $sortLink('published_at', 'Publicação') ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item):
                        [$label, $badge] = $statusBadge[$item['status']] ?? ['—', 'badge-muted']; ?>
                        <tr>
                            <?php if (has_permission('news.edit')): ?><td><input type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" class="bulk-cb"></td><?php endif; ?>
                            <td>
                                <?= e($item['title']) ?>
                                <?php if ((int) $item['is_featured'] === 1): ?><span class="badge badge-info" style="margin-left:.4rem;">★ destaque</span><?php endif; ?>
                                <div class="muted text-sm">/<?= e($item['slug']) ?></div>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= e($label) ?></span></td>
                            <td class="muted"><?= (int) $item['views'] ?></td>
                            <td class="muted text-sm"><?= e(format_date($item['published_at'] ?: $item['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <?php if (has_permission('news.edit')): ?>
                                        <a href="/admin/noticias/<?= (int) $item['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                                    <?php endif; ?>
                                    <?php if (has_permission('news.delete')): ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-confirm="Mover a notícia &quot;<?= e($item['title']) ?>&quot; para a lixeira?"
                                            data-form="del-news-<?= (int) $item['id'] ?>">Excluir</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php foreach ($items as $item): if (has_permission('news.delete')): ?>
            <form method="post" action="/admin/noticias/<?= (int) $item['id'] ?>/excluir" id="del-news-<?= (int) $item['id'] ?>" style="display:none;"><?= csrf_field() ?></form>
        <?php endif; endforeach; ?>

        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/noticias', 'query' => ['q' => $search, 'status' => $status, 'sort' => $sort, 'dir' => $dir]]) ?>
    <?php endif; ?>
</div>

<script>
function toggleAll(cb) {
    document.querySelectorAll('.bulk-cb').forEach(function (c) { c.checked = cb.checked; });
}
function submitBulk() {
    var form = document.getElementById('bulkForm');
    var action = form.bulk_action.value;
    var checked = form.querySelectorAll('.bulk-cb:checked').length;
    if (!action) { window.showToast && window.showToast('Selecione uma ação', 'error'); return; }
    if (!checked) { window.showToast && window.showToast('Selecione ao menos uma notícia', 'error'); return; }
    if (action === 'delete' && !confirm('Excluir as notícias selecionadas?')) return;
    form.submit();
}
</script>
