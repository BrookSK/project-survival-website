<?php
/** @var array|null $faq_item */
/** @var array $categories */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $faq_item !== null;
$action = $isEdit ? '/admin/faq/' . (int) $faq_item['id'] : '/admin/faq';
$val = fn(string $k, $d = '') => e(old($k, $faq_item[$k] ?? $d));
$catId = (int) old('category_id', $faq_item['category_id'] ?? 0);
$active = (int) old('is_active', $faq_item['is_active'] ?? 1);
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar pergunta' : 'Nova pergunta' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-group">
            <label for="question">Pergunta</label>
            <input type="text" id="question" name="question" value="<?= $val('question') ?>" required>
            <?php if (isset($errors['question'])): ?><div class="field-error"><?= e($errors['question']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="answer">Resposta</label>
            <textarea id="answer" name="answer" data-editor required><?= $val('answer') ?></textarea>
            <?php if (isset($errors['answer'])): ?><div class="field-error"><?= e($errors['answer']) ?></div><?php endif; ?>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Categoria</label>
                <select id="category_id" name="category_id">
                    <option value="0">— Sem categoria —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Ordem</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= $val('sort_order', '0') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?>>
                Pergunta ativa (visível no site)
            </label>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar' : 'Criar' ?></button>
        <a href="/admin/faq" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
