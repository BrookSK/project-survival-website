<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">FAQ</h1>
        <p class="page-subtitle"><?= (int) $total ?> pergunta(s) cadastrada(s).</p>
    </div>
    <?php if (has_permission('faq.create')): ?>
        <a href="/admin/faq/criar" class="btn btn-primary">+ Nova pergunta</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/faq">
            <input type="text" name="q" class="search" placeholder="Buscar pergunta..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon">❓</div>
            <p>Nenhuma pergunta cadastrada.</p>
            <?php if (has_permission('faq.create')): ?><a href="/admin/faq/criar" class="btn btn-primary">Criar a primeira</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Pergunta</th><th>Categoria</th><th>Ativa</th><th>Ordem</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $f): ?>
                    <tr>
                        <td><?= e($f['question']) ?></td>
                        <td class="muted"><?= e($f['category_name'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $f['is_active'] === 1): ?>
                                <span class="badge badge-success">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Não</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= (int) $f['sort_order'] ?></td>
                        <td>
                            <div class="actions">
                                <?php if (has_permission('faq.edit')): ?>
                                    <a href="/admin/faq/<?= (int) $f['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                                <?php endif; ?>
                                <?php if (has_permission('faq.delete')): ?>
                                    <form method="post" action="/admin/faq/<?= (int) $f['id'] ?>/excluir" id="del-faq-<?= (int) $f['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-confirm="Excluir esta pergunta?" data-form="del-faq-<?= (int) $f['id'] ?>">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/faq', 'query' => ['q' => $search]]) ?>
    <?php endif; ?>
</div>
