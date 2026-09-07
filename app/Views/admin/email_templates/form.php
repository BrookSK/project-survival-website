<?php
/** @var array $template */
/** @var array $errors */
$errors = $errors ?? [];
$active = (int) old('is_active', $template['is_active']);
?>
<div class="page-header">
    <h1 class="page-title">Editar template: <?= e($template['name']) ?></h1>
</div>

<form method="post" action="/admin/email-templates/<?= (int) $template['id'] ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <?php if ($template['placeholders']): ?>
            <div class="alert alert-info" style="margin-bottom:1.25rem;">
                <span>Placeholders disponíveis: <strong><?= e($template['placeholders']) ?></strong></span>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="subject">Assunto</label>
            <input type="text" id="subject" name="subject" value="<?= e(old('subject', $template['subject'])) ?>" required>
            <?php if (isset($errors['subject'])): ?><div class="field-error"><?= e($errors['subject']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="body">Conteúdo (HTML)</label>
            <textarea id="body" name="body" data-editor><?= e(old('body', $template['body'])) ?></textarea>
            <?php if (isset($errors['body'])): ?><div class="field-error"><?= e($errors['body']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?>>
                Template ativo
            </label>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar template</button>
        <a href="/admin/email-templates" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
