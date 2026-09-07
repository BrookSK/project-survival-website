<?php
/** @var array|null $banner */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $banner !== null;
$action = $isEdit ? '/admin/banners/' . (int) $banner['id'] : '/admin/banners';
$val = fn(string $k, $d = '') => e(old($k, $banner[$k] ?? $d));
$active = (int) old('is_active', $banner['is_active'] ?? 1);
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar banner' : 'Novo banner' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-row">
            <div class="form-group">
                <label for="title">Título</label>
                <input type="text" id="title" name="title" value="<?= $val('title') ?>">
            </div>
            <div class="form-group">
                <label for="position">Posição</label>
                <input type="text" id="position" name="position" value="<?= $val('position', 'home_hero') ?>" required>
                <?php if (isset($errors['position'])): ?><div class="field-error"><?= e($errors['position']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="subtitle">Subtítulo</label>
            <input type="text" id="subtitle" name="subtitle" value="<?= $val('subtitle') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="image">Imagem (desktop)</label>
                <input type="file" id="image" name="image" accept="image/*" data-image-input data-preview="ban-preview">
                <?php if (isset($errors['image'])): ?><div class="field-error"><?= e($errors['image']) ?></div><?php endif; ?>
                <img id="ban-preview" src="<?= $isEdit && $banner['image_path'] ? e(uploaded($banner['image_path'])) : '' ?>"
                     alt="" style="max-width:280px;margin-top:.75rem;border-radius:8px;<?= $isEdit && $banner['image_path'] ? '' : 'display:none;' ?>">
            </div>
            <div class="form-group">
                <label for="image_mobile">Imagem (mobile) <span class="label-hint">(opcional)</span></label>
                <input type="file" id="image_mobile" name="image_mobile" accept="image/*" data-image-input data-preview="ban-preview-m">
                <?php if (isset($errors['image_mobile'])): ?><div class="field-error"><?= e($errors['image_mobile']) ?></div><?php endif; ?>
                <img id="ban-preview-m" src="<?= $isEdit && !empty($banner['image_mobile']) ? e(uploaded($banner['image_mobile'])) : '' ?>"
                     alt="" style="max-width:180px;margin-top:.75rem;border-radius:8px;<?= $isEdit && !empty($banner['image_mobile']) ? '' : 'display:none;' ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="link_url">URL do link</label>
                <input type="url" id="link_url" name="link_url" value="<?= $val('link_url') ?>">
                <?php if (isset($errors['link_url'])): ?><div class="field-error"><?= e($errors['link_url']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="link_label">Texto do botão</label>
                <input type="text" id="link_label" name="link_label" value="<?= $val('link_label') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="starts_at">Início da exibição</label>
                <input type="datetime-local" id="starts_at" name="starts_at" value="<?= e(old('starts_at', $banner['starts_at'] ? str_replace(' ', 'T', substr($banner['starts_at'],0,16)) : '')) ?>">
            </div>
            <div class="form-group">
                <label for="ends_at">Fim da exibição</label>
                <input type="datetime-local" id="ends_at" name="ends_at" value="<?= e(old('ends_at', $banner['ends_at'] ? str_replace(' ', 'T', substr($banner['ends_at'],0,16)) : '')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="sort_order">Ordem</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= $val('sort_order', '0') ?>">
            </div>
            <div class="form-group">
                <label class="checkbox-row" style="margin-top:1.8rem;">
                    <input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?>>
                    Banner ativo
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar' : 'Criar banner' ?></button>
        <a href="/admin/banners" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
