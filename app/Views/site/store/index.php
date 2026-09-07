<?php
/**
 * @var array $categories
 * @var array $products
 * @var string $activeCategory
 * @var bool $offline
 * @var bool $loggedIn
 */
?>
<section class="page-hero">
    <div class="container">
        <h1>Loja</h1>
        <p>Cosméticos e itens do Project Survival.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <?php if ($offline && empty($products)): ?>
            <div class="site-alert site-alert-error">Loja temporariamente indisponível. Tente novamente em instantes.</div>
        <?php else: ?>

            <?php
            // Filtro por tipo de produto (campo do contrato). Deriva dos tipos
            // realmente presentes no catálogo para garantir correspondência.
            $typeLabels = ['skin' => 'Skins', 'bundle' => 'Pacotes', 'dlc' => 'DLCs', 'boost' => 'Boosts'];
            $presentTypes = [];
            foreach ($products as $p) {
                if (!empty($p['type']) && !in_array($p['type'], $presentTypes, true)) {
                    $presentTypes[] = $p['type'];
                }
            }
            ?>
            <?php if (count($presentTypes) > 1): ?>
                <div class="store-filters" id="storeFilters">
                    <button type="button" class="store-filter active" data-category="">Todos</button>
                    <?php foreach ($presentTypes as $t): ?>
                        <button type="button" class="store-filter" data-category="<?= e($t) ?>"><?= e($typeLabels[$t] ?? ucfirst($t)) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="empty-state"><p>Nenhum produto disponível no momento.</p></div>
            <?php else: ?>
                <div class="store-grid" id="storeGrid">
                    <?php foreach ($products as $p): ?>
                        <div class="store-cell" data-category="<?= e($p['type']) ?>">
                            <?= partial('site.store._card', ['product' => $p]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$loggedIn): ?>
                <p class="muted mt-3" style="text-align:center;">
                    <a href="/login">Entre na sua conta</a> para ver o que você já possui.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<script>
// Filtro de categoria simples (client-side). O catálogo já veio da API.
(function () {
    var filters = document.getElementById('storeFilters');
    var grid = document.getElementById('storeGrid');
    if (!filters || !grid) return;
    filters.addEventListener('click', function (e) {
        var btn = e.target.closest('.store-filter');
        if (!btn) return;
        filters.querySelectorAll('.store-filter').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var cat = btn.getAttribute('data-category');
        grid.querySelectorAll('.store-cell').forEach(function (cell) {
            cell.style.display = (!cat || cell.getAttribute('data-category') === cat) ? '' : 'none';
        });
    });
})();
</script>
