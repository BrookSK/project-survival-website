<?php
/** @var array|null $album */
/** @var array $albumItems */
/** @var array $errors */
$errors = $errors ?? [];
$isEdit = $album !== null;
$action = $isEdit ? '/admin/galeria/' . (int) $album['id'] : '/admin/galeria';
$val = fn(string $k, $d = '') => e(old($k, $album[$k] ?? $d));
$active = (int) old('is_active', $album['is_active'] ?? 1);
?>
<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? 'Editar álbum' : 'Novo álbum' ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="form-row">
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
            <div class="form-group">
                <label class="checkbox-row" style="margin-top:1.8rem;">
                    <input type="checkbox" name="is_active" value="1" <?= $active === 1 ? 'checked' : '' ?>>
                    Álbum ativo
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar álbum' : 'Criar álbum' ?></button>
        <a href="/admin/galeria" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php if ($isEdit): ?>
<div class="card">
    <div class="card-title">Itens do álbum</div>

    <?php if (empty($albumItems)): ?>
        <p class="muted mb-2">Nenhum item adicionado ainda.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
            <?php foreach ($albumItems as $it): ?>
                <div style="background:var(--bg-elev-2);border:1px solid var(--border);border-radius:8px;overflow:hidden;">
                    <?php if ($it['type'] === 'image' && $it['file_path']): ?>
                        <img src="<?= e(uploaded($it['file_path'])) ?>" alt="<?= e($it['alt_text']) ?>" style="width:100%;height:110px;object-fit:cover;display:block;">
                    <?php else: ?>
                        <div style="height:110px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;">🎬</div>
                    <?php endif; ?>
                    <div style="padding:.5rem;">
                        <div class="text-sm" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($it['title'] ?: ($it['type'] === 'video' ? 'Vídeo' : 'Imagem')) ?></div>
                        <form method="post" action="/admin/galeria/itens/<?= (int) $it['id'] ?>/excluir" id="del-item-<?= (int) $it['id'] ?>" style="margin-top:.4rem;">
                            <?= csrf_field() ?>
                            <button type="button" class="btn btn-danger btn-sm" style="width:100%;"
                                data-confirm="Remover este item?" data-form="del-item-<?= (int) $it['id'] ?>">Remover</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="background:var(--bg-elev-2);">
        <div class="card-title">Adicionar item</div>
        <form method="post" action="/admin/galeria/<?= (int) $album['id'] ?>/itens" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Tipo</label>
                    <select id="type" name="type" onchange="toggleItemType(this.value)">
                        <option value="image">Imagem</option>
                        <option value="video">Vídeo (URL)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="item_title">Título (opcional)</label>
                    <input type="text" id="item_title" name="item_title">
                </div>
            </div>
            <div class="form-group" id="field-image">
                <label for="image">Imagem</label>
                <input type="file" id="image" name="image" accept="image/*">
                <?php if (isset($errors['image'])): ?><div class="field-error"><?= e($errors['image']) ?></div><?php endif; ?>
            </div>
            <div class="form-group" id="field-video" style="display:none;">
                <label for="video_url">URL do vídeo</label>
                <input type="url" id="video_url" name="video_url" placeholder="https://...">
                <?php if (isset($errors['video_url'])): ?><div class="field-error"><?= e($errors['video_url']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="alt_text">Texto alternativo (acessibilidade)</label>
                <input type="text" id="alt_text" name="alt_text">
            </div>
            <button type="submit" class="btn btn-primary">Adicionar item</button>
        </form>
    </div>
</div>

<script>
function toggleItemType(type) {
    document.getElementById('field-image').style.display = (type === 'image') ? 'block' : 'none';
    document.getElementById('field-video').style.display = (type === 'video') ? 'block' : 'none';
}
</script>
<?php endif; ?>
