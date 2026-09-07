<?php
/** @var array $items */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Vídeos</h1>
        <p class="page-subtitle">Trailers e vídeos (YouTube, Vimeo ou externos). Arraste para reordenar.</p>
    </div>
    <?php if (has_permission('videos.create')): ?>
        <a href="/admin/videos/criar" class="btn btn-primary">+ Novo vídeo</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon">🎞️</div><p>Nenhum vídeo cadastrado.</p></div>
    <?php else: ?>
        <ul class="sortable-list" id="videosList" data-reorder-url="/admin/videos/reordenar">
            <?php foreach ($items as $v): ?>
                <li class="sortable-item" data-id="<?= (int) $v['id'] ?>">
                    <span class="drag-handle" title="Arrastar">⠿</span>
                    <?php if ($v['thumbnail']): ?>
                        <img src="<?= e($v['thumbnail']) ?>" alt="" style="width:80px;height:45px;object-fit:cover;border-radius:6px;">
                    <?php endif; ?>
                    <div class="sortable-body">
                        <strong><?= e($v['title']) ?></strong>
                        <span class="badge badge-muted"><?= e(ucfirst($v['provider'])) ?></span>
                        <?php if ((int) $v['is_featured'] === 1): ?><span class="badge badge-info">★ trailer</span><?php endif; ?>
                        <?php if ((int) $v['is_active'] === 1): ?><span class="badge badge-success">Ativo</span><?php else: ?><span class="badge badge-danger">Inativo</span><?php endif; ?>
                    </div>
                    <div class="actions">
                        <?php if (has_permission('videos.edit')): ?>
                            <a href="/admin/videos/<?= (int) $v['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                        <?php endif; ?>
                        <?php if (has_permission('videos.delete')): ?>
                            <form method="post" action="/admin/videos/<?= (int) $v['id'] ?>/excluir" id="del-vid-<?= (int) $v['id'] ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="button" class="btn btn-danger btn-sm" data-confirm="Excluir este vídeo?" data-form="del-vid-<?= (int) $v['id'] ?>">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
