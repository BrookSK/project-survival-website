<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
/** @var string $status */
$statusBadge = [
    'new' => ['Novo', 'badge-info'],
    'read' => ['Lido', 'badge-muted'],
    'replied' => ['Respondido', 'badge-success'],
    'archived' => ['Arquivado', 'badge-warning'],
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Mensagens</h1>
        <p class="page-subtitle"><?= (int) $total ?> mensagem(ns) recebida(s).</p>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/mensagens">
            <input type="text" name="q" class="search" placeholder="Buscar por nome, e-mail ou assunto..." value="<?= e($search) ?>">
            <select name="status">
                <option value="">Todos</option>
                <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Novos</option>
                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Lidos</option>
                <option value="replied" <?= $status === 'replied' ? 'selected' : '' ?>>Respondidos</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arquivados</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon">✉️</div><p>Nenhuma mensagem encontrada.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Assunto</th><th>Remetente</th><th>Status</th><th>Recebida</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $m):
                    [$label, $badge] = $statusBadge[$m['status']] ?? ['—', 'badge-muted'];
                    $weight = $m['status'] === 'new' ? 'font-weight:600;' : ''; ?>
                    <tr>
                        <td style="<?= $weight ?>"><a href="/admin/mensagens/<?= (int) $m['id'] ?>"><?= e($m['subject']) ?></a></td>
                        <td><?= e($m['name']) ?><div class="muted text-sm"><?= e($m['email']) ?></div></td>
                        <td><span class="badge <?= $badge ?>"><?= e($label) ?></span></td>
                        <td class="muted text-sm"><?= e(format_date($m['created_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <a href="/admin/mensagens/<?= (int) $m['id'] ?>" class="btn btn-secondary btn-sm">Ver</a>
                                <?php if (has_permission('messages.delete')): ?>
                                    <form method="post" action="/admin/mensagens/<?= (int) $m['id'] ?>/excluir" id="del-msg-<?= (int) $m['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-danger btn-sm" data-confirm="Excluir esta mensagem?" data-form="del-msg-<?= (int) $m['id'] ?>">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/mensagens', 'query' => ['q' => $search, 'status' => $status]]) ?>
    <?php endif; ?>
</div>
