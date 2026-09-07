<?php
/** @var array $items */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Galeria</h1>
        <p class="page-subtitle">Álbuns de imagens e vídeos.</p>
    </div>
    <?php if (has_permission('gallery.create')): ?>
        <a href="/admin/galeria/criar" class="btn btn-primary">+ Novo álbum</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon">🖼️</div>
            <p>Nenhum álbum criado.</p>
            <?php if (has_permission('gallery.create')): ?><a href="/admin/galeria/criar" class="btn btn-primary">Criar o primeiro álbum</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Álbum</th><th>Itens</th><th>Ativo</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $a): ?>
                    <tr>
                        <td>
                            <?= e($a['title']) ?>
                            <div class="muted text-sm">/<?= e($a['slug']) ?></div>
                        </td>
                        <td class="muted"><?= (int) $a['items_count'] ?></td>
                        <td>
                            <?php if ((int) $a['is_active'] === 1): ?>
                                <span class="badge badge-success">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if (has_permission('gallery.edit')): ?>
                                    <a href="/admin/galeria/<?= (int) $a['id'] ?>/editar" class="btn btn-secondary btn-sm">Gerenciar</a>
                                <?php endif; ?>
                                <?php if (has_permission('gallery.delete')): ?>
                                    <form method="post" action="/admin/galeria/<?= (int) $a['id'] ?>/excluir" id="del-alb-<?= (int) $a['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-confirm="Excluir o álbum &quot;<?= e($a['title']) ?>&quot; e todos os seus itens?"
                                            data-form="del-alb-<?= (int) $a['id'] ?>">Excluir</button>
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
