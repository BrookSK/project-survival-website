<?php
/** @var array $items */
$typeLabels = [
    'content' => 'Conteúdo', 'features' => 'Características', 'screenshots' => 'Screenshots',
    'trailer' => 'Trailer', 'news' => 'Notícias', 'faq' => 'FAQ', 'cta' => 'Chamada (CTA)',
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Seções da Home</h1>
        <p class="page-subtitle">Arraste para reordenar. Ative/desative e edite cada seção.</p>
    </div>
    <?php if (has_permission('home.edit')): ?>
        <a href="/admin/home/criar" class="btn btn-primary">+ Nova seção</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon">🏠</div><p>Nenhuma seção configurada.</p></div>
    <?php else: ?>
        <ul class="sortable-list" id="homeSections" data-reorder-url="/admin/home/reordenar">
            <?php foreach ($items as $s): ?>
                <li class="sortable-item" data-id="<?= (int) $s['id'] ?>">
                    <span class="drag-handle" title="Arrastar">⠿</span>
                    <div class="sortable-body">
                        <strong><?= e($s['title'] ?: $s['key']) ?></strong>
                        <span class="badge badge-muted"><?= e($typeLabels[$s['type']] ?? $s['type']) ?></span>
                        <?php if ((int) $s['is_active'] === 1): ?>
                            <span class="badge badge-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inativa</span>
                        <?php endif; ?>
                        <div class="muted text-sm">chave: <?= e($s['key']) ?></div>
                    </div>
                    <div class="actions">
                        <?php if (has_permission('home.edit')): ?>
                            <a href="/admin/home/<?= (int) $s['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                            <form method="post" action="/admin/home/<?= (int) $s['id'] ?>/excluir" id="del-hs-<?= (int) $s['id'] ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="button" class="btn btn-danger btn-sm" data-confirm="Excluir esta seção?" data-form="del-hs-<?= (int) $s['id'] ?>">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
