<?php
/** @var array $items */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Banners</h1>
        <p class="page-subtitle">Slides e destaques exibidos no site.</p>
    </div>
    <?php if (has_permission('banners.create')): ?>
        <a href="/admin/banners/criar" class="btn btn-primary">+ Novo banner</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon">🎬</div>
            <p>Nenhum banner cadastrado.</p>
            <?php if (has_permission('banners.create')): ?><a href="/admin/banners/criar" class="btn btn-primary">Criar o primeiro</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Imagem</th><th>Título</th><th>Posição</th><th>Ativo</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $b): ?>
                    <tr>
                        <td>
                            <?php if ($b['image_path']): ?>
                                <img src="<?= e(uploaded($b['image_path'])) ?>" alt="" style="width:80px;height:45px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($b['title'] ?: '(sem título)') ?></td>
                        <td class="muted"><?= e($b['position']) ?></td>
                        <td>
                            <?php if ((int) $b['is_active'] === 1): ?>
                                <span class="badge badge-success">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if (has_permission('banners.edit')): ?>
                                    <a href="/admin/banners/<?= (int) $b['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                                <?php endif; ?>
                                <?php if (has_permission('banners.delete')): ?>
                                    <form method="post" action="/admin/banners/<?= (int) $b['id'] ?>/excluir" id="del-ban-<?= (int) $b['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-confirm="Excluir este banner?" data-form="del-ban-<?= (int) $b['id'] ?>">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
