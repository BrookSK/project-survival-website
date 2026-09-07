<?php
/**
 * @var array $policy
 * @var array|null $draft
 * @var string $suggestedContent
 * @var string $lastVersion
 * @var array $errors
 */
$errors = $errors ?? [];
$isDraft = $draft !== null;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Editar: <?= e($policy['title']) ?></h1>
        <p class="page-subtitle">Crie ou edite um rascunho. A publicação torna a versão vigente e imutável.</p>
    </div>
    <a href="/admin/privacidade/documentos/<?= (int) $policy['id'] ?>/historico" class="btn btn-secondary">Histórico</a>
</div>

<div class="alert alert-info" style="margin-bottom:1.25rem;">
    <span>Revise juridicamente antes de publicar. Ao publicar, a versão anterior é arquivada (nunca apagada).</span>
</div>

<div class="card">
    <form method="post" action="/admin/privacidade/documentos/<?= (int) $policy['id'] ?>/salvar" novalidate>
        <?= csrf_field() ?>
        <?php if ($isDraft): ?><input type="hidden" name="draft_id" value="<?= (int) $draft['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="version">Versão</label>
                <input type="text" id="version" name="version" value="<?= e($isDraft ? $draft['version'] : old('version')) ?>"
                       placeholder="Ex.: 1.0" <?= $isDraft ? 'readonly' : '' ?>>
                <?php if (isset($errors['version'])): ?><div class="field-error"><?= e($errors['version']) ?></div><?php endif; ?>
                <?php if (!$isDraft && $lastVersion !== ''): ?><div class="label-hint">Última versão: <?= e($lastVersion) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="effective_at">Data de vigência (opcional)</label>
                <input type="datetime-local" id="effective_at" name="effective_at"
                       value="<?= e($isDraft && !empty($draft['effective_at']) ? date('Y-m-d\TH:i', strtotime($draft['effective_at'])) : '') ?>">
                <div class="label-hint">Se vazio, passa a valer na publicação.</div>
            </div>
        </div>

        <div class="form-group">
            <label for="content">Conteúdo</label>
            <textarea id="content" name="content" data-editor rows="18"><?= e($suggestedContent) ?></textarea>
            <div class="label-hint">HTML é sanitizado automaticamente ao salvar.</div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar rascunho</button>
            <a href="/admin/privacidade/documentos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
