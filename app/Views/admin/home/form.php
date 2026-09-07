<?php
/** @var array|null $section */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $section !== null;
$action = $isEdit ? '/admin/home/' . (int) $section['id'] : '/admin/home';
$val = fn(string $k, $d = '') => e(old($k, $section[$k] ?? $d));
$type = old('type', $section['type'] ?? 'content');
$active = (int) old('is_active', $section['is_active'] ?? 1);
$types = ['content' => 'Conteúdo', 'features' => 'Características', 'screenshots' => 'Screenshots', 'trailer' => 'Trailer', 'news' => 'Notícias', 'faq' => 'FAQ', 'cta' => 'Chamada (CTA)'];
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar seção' : 'Nova seção' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-row">
            <div class="form-group">
                <label for="key">Chave <span class="label-hint">(identificador único)</span></label>
                <input type="text" id="key" name="key" value="<?= $val('key') ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                <?php if (isset($errors['key'])): ?><div class="field-error"><?= e($errors['key']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="type">Tipo</label>
                <select id="type" name="type">
                    <?php foreach ($types as $v => $label): ?>
                        <option value="<?= e($v) ?>" <?= $type === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" value="<?= $val('title') ?>">
        </div>
        <div class="form-group">
            <label for="subtitle">Subtítulo</label>
            <input type="text" id="subtitle" name="subtitle" value="<?= $val('subtitle') ?>">
        </div>
        <div class="form-group">
            <label for="content">Descrição / Conteúdo <span class="label-hint">(para seções de conteúdo/CTA)</span></label>
            <textarea id="content" name="content" data-editor><?= $val('content') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="button_label">Texto do botão</label>
                <input type="text" id="button_label" name="button_label" value="<?= $val('button_label') ?>">
            </div>
            <div class="form-group">
                <label for="button_url">URL do botão</label>
                <input type="text" id="button_url" name="button_url" value="<?= $val('button_url') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="image">Imagem</label>
            <input type="file" id="image" name="image" accept="image/*" data-image-input data-preview="hs-preview">
            <img id="hs-preview" src="<?= $isEdit && !empty($section['image']) ? e(uploaded($section['image'])) : '' ?>"
                 alt="" style="max-width:280px;margin-top:.75rem;border-radius:8px;<?= $isEdit && !empty($section['image']) ? '' : 'display:none;' ?>">
        </div>
        <div class="form-group">
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?>>
                Seção ativa (visível na home)
            </label>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar' : 'Criar seção' ?></button>
        <a href="/admin/home" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
