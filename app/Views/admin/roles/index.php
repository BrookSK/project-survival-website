<?php
/** @var array $items */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Perfis</h1>
        <p class="page-subtitle">Perfis de acesso e suas permissões.</p>
    </div>
    <?php if (has_permission('roles.create')): ?>
        <a href="/admin/perfis/criar" class="btn btn-primary">+ Novo perfil</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Nome</th><th>Slug</th><th>Usuários</th><th>Tipo</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $r): ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td class="muted"><?= e($r['slug']) ?></td>
                    <td class="muted"><?= (int) $r['users_count'] ?></td>
                    <td>
                        <?php if ((int) $r['is_system'] === 1): ?>
                            <span class="badge badge-info">Sistema</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Personalizado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions">
                            <?php if (has_permission('roles.edit')): ?>
                                <a href="/admin/perfis/<?= (int) $r['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                            <?php endif; ?>
                            <?php if (has_permission('roles.delete') && (int) $r['is_system'] === 0): ?>
                                <form method="post" action="/admin/perfis/<?= (int) $r['id'] ?>/excluir" id="del-role-<?= (int) $r['id'] ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                        data-confirm="Excluir o perfil &quot;<?= e($r['name']) ?>&quot;?"
                                        data-form="del-role-<?= (int) $r['id'] ?>">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
