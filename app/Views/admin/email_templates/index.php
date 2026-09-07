<?php
/** @var array $items */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Templates de e-mail</h1>
        <p class="page-subtitle">Personalize os e-mails enviados pelo sistema.</p>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Template</th><th>Assunto</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $t): ?>
                <tr>
                    <td><?= e($t['name']) ?><div class="muted text-sm"><?= e($t['key']) ?></div></td>
                    <td class="muted"><?= e($t['subject']) ?></td>
                    <td>
                        <?php if ((int) $t['is_active'] === 1): ?><span class="badge badge-success">Ativo</span><?php else: ?><span class="badge badge-muted">Inativo</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if (has_permission('email_templates.edit')): ?>
                            <a href="/admin/email-templates/<?= (int) $t['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
