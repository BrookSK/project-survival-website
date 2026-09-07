<?php
/** @var array|null $page_item */
/** @var array|null $meta */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $page_item !== null;
$action = $isEdit ? '/admin/paginas/' . (int) $page_item['id'] : '/admin/paginas';
$val = function (string $key, $default = '') use ($page_item) {
    return e(old($key, $page_item[$key] ?? $default));
};
$metaVal = function (string $key, $default = '') use ($meta) {
    return e(old($key, $meta[$key] ?? $default));
};
$status = old('status', $page_item['status'] ?? 'draft');
$robots = old('robots', $meta['robots'] ?? 'index,follow');
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar página' : 'Nova página' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="card">
        <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" data-slug-source value="<?= $val('title') ?>" required>
            <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="slug">Slug <span class="label-hint">(URL amigável)</span></label>
            <input type="text" id="slug" name="slug" data-slug-target value="<?= $val('slug') ?>" required>
            <?php if (isset($errors['slug'])): ?><div class="field-error"><?= e($errors['slug']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="content">Conteúdo</label>
            <textarea id="content" name="content" data-editor><?= $val('content') ?></textarea>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicada</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-title">SEO</div>
        <div class="form-group">
            <label for="seo_title">Título SEO</label>
            <input type="text" id="seo_title" name="seo_title" value="<?= $metaVal('seo_title') ?>" maxlength="200">
        </div>
        <div class="form-group">
            <label for="seo_description">Descrição SEO</label>
            <textarea id="seo_description" name="seo_description" rows="3" maxlength="300"><?= $metaVal('seo_description') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="seo_keywords">Palavras-chave</label>
                <input type="text" id="seo_keywords" name="seo_keywords" value="<?= $metaVal('seo_keywords') ?>">
            </div>
            <div class="form-group">
                <label for="og_image">Imagem social (URL)</label>
                <input type="text" id="og_image" name="og_image" value="<?= $metaVal('og_image') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="robots">Indexação</label>
            <select id="robots" name="robots">
                <option value="index,follow" <?= $robots === 'index,follow' ? 'selected' : '' ?>>Indexar (index,follow)</option>
                <option value="noindex,nofollow" <?= $robots === 'noindex,nofollow' ? 'selected' : '' ?>>Não indexar (noindex,nofollow)</option>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar alterações' : 'Criar página' ?></button>
        <a href="/admin/paginas" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
