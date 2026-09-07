<?php
/** @var array $items */
/** @var array $errors */
$errors = $errors ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Categorias</h1>
        <p class="page-subtitle">Categorias usadas para organizar as notícias.</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Categorias existentes</div>
        <?php if (empty($items)): ?>
            <div class="empty-state"><div class="icon">🏷️</div><p>Nenhuma categoria cadastrada.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Nome</th><th>Slug</th><th>Notícias</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $c): ?>
                        <tr>
                            <td><?= e($c['name']) ?></td>
                            <td class="muted"><?= e($c['slug']) ?></td>
                            <td class="muted"><?= (int) $c['news_count'] ?></td>
                            <td>
                                <div class="actions">
                                    <?php if (has_permission('categories.edit')): ?>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick='fillCategory(<?= json_encode(["id"=>(int)$c["id"],"name"=>$c["name"],"slug"=>$c["slug"],"description"=>$c["description"]], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
                                    <?php endif; ?>
                                    <?php if (has_permission('categories.delete')): ?>
                                        <form method="post" action="/admin/categorias/<?= (int) $c['id'] ?>/excluir" id="del-cat-<?= (int) $c['id'] ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                data-confirm="Excluir a categoria &quot;<?= e($c['name']) ?>&quot;?"
                                                data-form="del-cat-<?= (int) $c['id'] ?>">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if (has_permission('categories.create')): ?>
    <div class="card">
        <div class="card-title" id="cat-form-title">Nova categoria</div>
        <form method="post" action="/admin/categorias" id="catForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" data-slug-source value="<?= old('name') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" data-slug-target value="<?= old('slug') ?>" required>
                <?php if (isset($errors['slug'])): ?><div class="field-error"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" rows="3"><?= old('description') ?></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-row">
                    <input type="checkbox" name="is_active" value="1" checked id="cat_active"> Categoria ativa
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="resetCategory()">Limpar</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function fillCategory(c) {
    var form = document.getElementById('catForm');
    form.action = '/admin/categorias/' + c.id;
    document.getElementById('cat-form-title').textContent = 'Editar categoria';
    form.name.value = c.name || '';
    form.slug.value = c.slug || '';
    form.description.value = c.description || '';
    window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
}
function resetCategory() {
    var form = document.getElementById('catForm');
    form.action = '/admin/categorias';
    document.getElementById('cat-form-title').textContent = 'Nova categoria';
    form.reset();
}
</script>
