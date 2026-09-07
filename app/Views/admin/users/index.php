<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
$currentId = (int) (auth_user()['id'] ?? 0);
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Usuários</h1>
        <p class="page-subtitle"><?= (int) $total ?> usuário(s) administrativo(s).</p>
    </div>
    <?php if (has_permission('users.create')): ?>
        <a href="/admin/usuarios/criar" class="btn btn-primary">+ Novo usuário</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/usuarios">
            <input type="text" name="q" class="search" placeholder="Buscar por nome ou e-mail..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon">👤</div><p>Nenhum usuário encontrado.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Perfis</th><th>Status</th><th>Último acesso</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?><?php if ((int)$u['id'] === $currentId): ?> <span class="badge badge-info">você</span><?php endif; ?></td>
                        <td class="muted"><?= e($u['email']) ?></td>
                        <td class="text-sm"><?= e(implode(', ', $u['role_names']) ?: '—') ?></td>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted text-sm"><?= e(format_date($u['last_login_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <?php if (has_permission('users.edit')): ?>
                                    <a href="/admin/usuarios/<?= (int) $u['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                                <?php endif; ?>
                                <?php if (has_permission('users.delete') && (int) $u['id'] !== $currentId): ?>
                                    <form method="post" action="/admin/usuarios/<?= (int) $u['id'] ?>/excluir" id="del-user-<?= (int) $u['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-confirm="Excluir o usuário &quot;<?= e($u['name']) ?>&quot;?"
                                            data-form="del-user-<?= (int) $u['id'] ?>">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/usuarios', 'query' => ['q' => $search]]) ?>
    <?php endif; ?>
</div>
