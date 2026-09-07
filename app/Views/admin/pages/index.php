<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
$statusBadge = ['published' => ['Publicada', 'badge-success'], 'draft' => ['Rascunho', 'badge-muted']];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Páginas</h1>
        <p class="page-subtitle"><?= (int) $total ?> página(s) no total.</p>
    </div>
    <?php if (has_permission('pages.create')): ?>
        <a href="/admin/paginas/criar" class="btn btn-primary">+ Nova página</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/paginas">
            <input type="text" name="q" class="search" placeholder="Buscar por título ou slug..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if ($search !== ''): ?><a href="/admin/paginas" class="btn btn-secondary">Limpar</a><?php endif; ?>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon">📄</div>
            <p>Nenhuma página encontrada.</p>
            <?php if (has_permission('pages.create')): ?>
                <a href="/admin/paginas/criar" class="btn btn-primary">Criar a primeira página</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Título</th><th>Slug</th><th>Status</th><th>Atualizada</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item):
                    [$label, $badge] = $statusBadge[$item['status']] ?? ['—', 'badge-muted']; ?>
                    <tr>
                        <td>
                            <?= e($item['title']) ?>
                            <?php if ((int) $item['is_system'] === 1): ?>
                                <span class="badge badge-info" style="margin-left:.4rem;">sistema</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted">/<?= e($item['slug']) ?></td>
                        <td><span class="badge <?= $badge ?>"><?= e($label) ?></span></td>
                        <td class="muted text-sm"><?= e(format_date($item['updated_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <?php if (has_permission('pages.edit')): ?>
                                    <a href="/admin/paginas/<?= (int) $item['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                                <?php endif; ?>
                                <?php if (has_permission('pages.delete') && (int) $item['is_system'] === 0): ?>
                                    <form method="post" action="/admin/paginas/<?= (int) $item['id'] ?>/excluir" id="del-page-<?= (int) $item['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-confirm="Excluir a página &quot;<?= e($item['title']) ?>&quot;?"
                                            data-form="del-page-<?= (int) $item['id'] ?>">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/paginas', 'query' => ['q' => $search]]) ?>
    <?php endif; ?>
</div>
