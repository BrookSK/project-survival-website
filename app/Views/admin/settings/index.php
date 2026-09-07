<?php
/** @var array $groups   slug => rótulo */
/** @var array $settings group => rows[] */
/** @var string $active */
$canEdit = has_permission('settings.edit');

/**
 * Renderiza o campo apropriado conforme o tipo da configuração.
 */
$renderField = function (array $s) {
    $key = $s['key'];
    $type = $s['type'];
    $value = $s['value'];
    $id = 'set_' . $key;

    if ($type === 'boolean') {
        $checked = ($value === '1' || $value === 'true') ? 'checked' : '';
        echo '<label class="checkbox-row"><input type="checkbox" id="' . e($id) . '" name="' . e($key) . '" value="1" ' . $checked . '> ' . e($s['label'] ?: $key) . '</label>';
        if (!empty($s['description'])) {
            echo '<div class="label-hint" style="margin-top:.35rem;">' . e($s['description']) . '</div>';
        }
        return;
    }

    echo '<label for="' . e($id) . '">' . e($s['label'] ?: $key);
    if (!empty($s['description'])) {
        echo ' <span class="label-hint">' . e($s['description']) . '</span>';
    }
    echo '</label>';

    if ((int) $s['is_secret'] === 1) {
        // Campos secretos: não expõe o valor atual; vazio = manter
        echo '<input type="password" id="' . e($id) . '" name="' . e($key) . '" value="" placeholder="•••••••• (deixe em branco para manter)" autocomplete="new-password">';
    } elseif ($type === 'text') {
        echo '<textarea id="' . e($id) . '" name="' . e($key) . '" rows="3">' . e($value) . '</textarea>';
    } elseif ($type === 'integer') {
        echo '<input type="number" id="' . e($id) . '" name="' . e($key) . '" value="' . e($value) . '">';
    } else {
        echo '<input type="text" id="' . e($id) . '" name="' . e($key) . '" value="' . e($value) . '">';
    }
};
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Configurações</h1>
        <p class="page-subtitle">Ajustes gerais do sistema, organizados por categoria.</p>
    </div>
</div>

<div class="card">
    <div class="toolbar" style="border-bottom:1px solid var(--border);padding-bottom:1rem;">
        <?php foreach ($groups as $slug => $label): ?>
            <a href="/admin/configuracoes?grupo=<?= e($slug) ?>"
               class="btn <?= $active === $slug ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <?php $rows = $settings[$active] ?? []; ?>
    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Nenhuma configuração neste grupo.</p></div>
    <?php else: ?>
        <form method="post" action="/admin/configuracoes" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="__group" value="<?= e($active) ?>">
            <?php foreach ($rows as $s): ?>
                <div class="form-group">
                    <?php $renderField($s); ?>
                </div>
            <?php endforeach; ?>
            <?php if ($canEdit): ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar configurações</button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<?php if ($active === 'email' && $canEdit): ?>
<div class="card">
    <div class="card-title">Enviar e-mail de teste</div>
    <p class="muted mb-2">Salve as configurações de SMTP antes de testar. O e-mail usará os valores atualmente salvos.</p>
    <form method="post" action="/admin/configuracoes/email/teste" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:end;">
        <?= csrf_field() ?>
        <div class="form-group" style="flex:1;min-width:240px;margin:0;">
            <label for="test_email">E-mail de destino</label>
            <input type="email" id="test_email" name="test_email" value="<?= e(auth_user()['email'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn btn-secondary">Enviar teste</button>
    </form>
</div>
<?php endif; ?>
