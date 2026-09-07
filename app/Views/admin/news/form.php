<?php
/** @var array|null $news_item */
/** @var array $allCategories */
/** @var array $selectedCats */
/** @var int $featuredLimit */
/** @var int $featuredCount */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $news_item !== null;
$action = $isEdit ? '/admin/noticias/' . (int) $news_item['id'] : '/admin/noticias';
$val = fn(string $k, $d = '') => e(old($k, $news_item[$k] ?? $d));
$status = old('status', $news_item['status'] ?? 'draft');
$selectedCats = array_map('intval', $selectedCats);
$isFeatured = (int) old('is_featured', $news_item['is_featured'] ?? 0);
$scheduledVal = '';
if ($isEdit && $news_item['status'] === 'scheduled' && $news_item['published_at']) {
    $scheduledVal = str_replace(' ', 'T', substr($news_item['published_at'], 0, 16));
}
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar notícia' : 'Nova notícia' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="card">
        <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" data-slug-source value="<?= $val('title') ?>" required>
            <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" data-slug-target value="<?= $val('slug') ?>" required>
            <?php if (isset($errors['slug'])): ?><div class="field-error"><?= e($errors['slug']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="excerpt">Resumo <span class="label-hint">(até 500 caracteres)</span></label>
            <textarea id="excerpt" name="excerpt" rows="3" maxlength="500"><?= $val('excerpt') ?></textarea>
            <?php if (isset($errors['excerpt'])): ?><div class="field-error"><?= e($errors['excerpt']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="content">Conteúdo</label>
            <textarea id="content" name="content" data-editor><?= $val('content') ?></textarea>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Publicação</div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" onchange="toggleSchedule(this.value)">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Agendada</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicada</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arquivada</option>
                </select>
            </div>
            <div class="form-group" id="schedule-field" style="<?= $status === 'scheduled' ? '' : 'display:none;' ?>">
                <label for="scheduled_at">Data de publicação agendada</label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= e(old('scheduled_at', $scheduledVal)) ?>">
                <?php if (isset($errors['scheduled_at'])): ?><div class="field-error"><?= e($errors['scheduled_at']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="featured_image">Imagem destacada</label>
                <input type="file" id="featured_image" name="featured_image" accept="image/*" data-image-input data-preview="img-preview">
                <?php if (isset($errors['featured_image'])): ?><div class="field-error"><?= e($errors['featured_image']) ?></div><?php endif; ?>
                <img id="img-preview" src="<?= $isEdit && $news_item['featured_image'] ? e(uploaded($news_item['featured_image'])) : '' ?>"
                     alt="" style="max-width:200px;margin-top:.75rem;border-radius:8px;<?= $isEdit && $news_item['featured_image'] ? '' : 'display:none;' ?>">
            </div>
            <div class="form-group">
                <label class="checkbox-row" style="margin-top:1.8rem;">
                    <input type="checkbox" name="is_featured" value="1" <?= $isFeatured === 1 ? 'checked' : '' ?>>
                    Destaque na home
                </label>
                <div class="label-hint mt-1"><?= (int) $featuredCount ?>/<?= (int) $featuredLimit ?> destaques usados.</div>
                <?php if (isset($errors['is_featured'])): ?><div class="field-error"><?= e($errors['is_featured']) ?></div><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($allCategories)): ?>
        <div class="form-group">
            <label>Categorias</label>
            <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
                <?php foreach ($allCategories as $cat): ?>
                    <label class="checkbox-row" style="margin:0;">
                        <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>"
                            <?= in_array((int) $cat['id'], $selectedCats, true) ? 'checked' : '' ?>>
                        <?= e($cat['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">SEO</div>
        <div class="form-group">
            <label for="seo_title">Título SEO</label>
            <input type="text" id="seo_title" name="seo_title" value="<?= $val('seo_title') ?>" maxlength="200">
        </div>
        <div class="form-group">
            <label for="seo_description">Descrição SEO</label>
            <textarea id="seo_description" name="seo_description" rows="3" maxlength="300"><?= $val('seo_description') ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar alterações' : 'Criar notícia' ?></button>
        <a href="/admin/noticias" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
function toggleSchedule(status) {
    document.getElementById('schedule-field').style.display = (status === 'scheduled') ? 'block' : 'none';
}
</script>
