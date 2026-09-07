<?php
/** @var array|null $video */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $video !== null;
$action = $isEdit ? '/admin/videos/' . (int) $video['id'] : '/admin/videos';
$val = fn(string $k, $d = '') => e(old($k, $video[$k] ?? $d));
$featured = (int) old('is_featured', $video['is_featured'] ?? 0);
$active = (int) old('is_active', $video['is_active'] ?? 1);
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar vídeo' : 'Novo vídeo' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" value="<?= $val('title') ?>" required>
            <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="url">URL do vídeo <span class="label-hint">(YouTube, Vimeo ou outra)</span></label>
            <input type="url" id="url" name="url" value="<?= $val('url') ?>" placeholder="https://youtube.com/watch?v=..." required>
            <?php if (isset($errors['url'])): ?><div class="field-error"><?= e($errors['url']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="thumbnail">Thumbnail (URL) <span class="label-hint">(opcional — detectada automaticamente p/ YouTube)</span></label>
            <input type="text" id="thumbnail" name="thumbnail" value="<?= $val('thumbnail') ?>">
        </div>
        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3"><?= $val('description') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="sort_order">Ordem</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= $val('sort_order', '0') ?>">
            </div>
            <div class="form-group" style="margin-top:1.8rem;">
                <label class="checkbox-row"><input type="checkbox" name="is_featured" value="1" <?= $featured === 1 ? 'checked' : '' ?>> Trailer principal (destaque)</label>
                <label class="checkbox-row mt-1"><input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?>> Ativo</label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar' : 'Criar vídeo' ?></button>
        <a href="/admin/videos" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
