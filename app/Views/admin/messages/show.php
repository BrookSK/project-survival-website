<?php
/** @var array $message */
$statusOptions = ['new' => 'Novo', 'read' => 'Lido', 'replied' => 'Respondido', 'archived' => 'Arquivado'];
?>
<div class="page-header">
    <h1 class="page-title"><?= e($message['subject']) ?></h1>
    <a href="/admin/mensagens" class="btn btn-secondary">← Voltar</a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Mensagem</div>
        <p style="white-space:pre-wrap;line-height:1.7;"><?= e($message['message']) ?></p>
    </div>

    <div>
        <div class="card">
            <div class="card-title">Remetente</div>
            <p class="mb-2"><strong><?= e($message['name']) ?></strong></p>
            <p class="mb-2"><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></p>
            <p class="muted text-sm">Recebida em <?= e(format_date($message['created_at'])) ?></p>
            <?php if ($message['ip_address']): ?>
                <p class="muted text-sm">IP: <?= e($message['ip_address']) ?></p>
            <?php endif; ?>
            <div class="mt-2">
                <a href="mailto:<?= e($message['email']) ?>?subject=<?= e('Re: ' . $message['subject']) ?>" class="btn btn-primary">Responder por e-mail</a>
            </div>
        </div>

        <?php if (has_permission('messages.edit')): ?>
        <div class="card">
            <div class="card-title">Status</div>
            <form method="post" action="/admin/mensagens/<?= (int) $message['id'] ?>/status">
                <?= csrf_field() ?>
                <div class="form-group">
                    <select name="status">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $message['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Atualizar status</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
