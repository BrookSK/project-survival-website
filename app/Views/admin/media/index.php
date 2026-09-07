<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
/** @var bool $gdAvailable */
/** @var bool $webpSupported */
use App\Models\Media;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Biblioteca de mídia</h1>
        <p class="page-subtitle"><?= (int) $total ?> arquivo(s). <?php if (!$gdAvailable): ?><span class="badge badge-warning">GD indisponível — sem variantes</span><?php elseif (!$webpSupported): ?><span class="badge badge-info">WebP indisponível</span><?php endif; ?></p>
    </div>
</div>

<?php if (has_permission('media.upload')): ?>
<div class="card">
    <div class="card-title">Enviar arquivos</div>
    <form method="post" action="/admin/midia" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
            <input type="file" name="files[]" accept="image/*" multiple required>
            <div class="label-hint mt-1">Formatos: JPG, PNG, WEBP, GIF. Variantes (thumbnail/medium/large) são geradas automaticamente quando possível.</div>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <div class="toolbar">
        <form method="get" action="/admin/midia">
            <input type="text" name="q" class="search" placeholder="Buscar por nome, título ou alt..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon">🗂️</div><p>Nenhuma mídia enviada ainda.</p></div>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($items as $m):
                $thumb = uploaded(Media::variant($m, 'thumbnail')); ?>
                <figure class="media-card">
                    <div class="media-thumb">
                        <img src="<?= e($thumb) ?>" alt="<?= e($m['alt_text'] ?: $m['original_name']) ?>" loading="lazy">
                    </div>
                    <figcaption>
                        <div class="media-name" title="<?= e($m['original_name']) ?>"><?= e($m['title'] ?: $m['original_name']) ?></div>
                        <div class="media-meta">
                            <?= strtoupper(e($m['extension'])) ?> ·
                            <?= $m['width'] ? (int) $m['width'] . '×' . (int) $m['height'] : '—' ?> ·
                            <?= number_format($m['size'] / 1024, 0, ',', '.') ?> KB
                        </div>
                        <div class="media-actions">
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick='mediaEdit(<?= json_encode(["id"=>(int)$m["id"],"title"=>$m["title"],"alt"=>$m["alt_text"],"url"=>uploaded($m["path"])], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
                            <?php if (has_permission('media.delete')): ?>
                                <form method="post" action="/admin/midia/<?= (int) $m['id'] ?>/excluir" id="del-media-<?= (int) $m['id'] ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="button" class="btn btn-danger btn-sm" data-confirm="Excluir este arquivo?" data-form="del-media-<?= (int) $m['id'] ?>">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $page, 'total' => $total, 'perPage' => $perPage, 'baseUrl' => '/admin/midia', 'query' => ['q' => $search]]) ?>
    <?php endif; ?>
</div>

<!-- Modal de edição de mídia -->
<div class="modal-backdrop" id="mediaModal">
    <div class="modal" role="dialog" aria-modal="true">
        <h3>Editar mídia</h3>
        <form method="post" id="mediaEditForm">
            <?= csrf_field() ?>
            <div style="text-align:center;margin-bottom:1rem;">
                <img id="mediaModalImg" src="" alt="" style="max-width:100%;max-height:200px;border-radius:8px;">
            </div>
            <div class="form-group">
                <label for="media_title">Título</label>
                <input type="text" id="media_title" name="title">
            </div>
            <div class="form-group">
                <label for="media_alt">Texto alternativo (acessibilidade)</label>
                <input type="text" id="media_alt" name="alt_text">
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="text" id="media_url" readonly onclick="this.select()">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('mediaModal').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function mediaEdit(m) {
    var modal = document.getElementById('mediaModal');
    var form = document.getElementById('mediaEditForm');
    form.action = '/admin/midia/' + m.id;
    document.getElementById('mediaModalImg').src = m.url;
    document.getElementById('media_title').value = m.title || '';
    document.getElementById('media_alt').value = m.alt || '';
    document.getElementById('media_url').value = m.url;
    modal.classList.add('open');
}
</script>
