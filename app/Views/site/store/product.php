<?php
/**
 * @var array $product
 * @var bool $loggedIn
 */
$p = $product;
$typeLabels = ['skin' => 'Skin', 'bundle' => 'Pacote', 'dlc' => 'DLC', 'boost' => 'Boost'];
$typeLabel = $typeLabels[$p['type']] ?? ($p['type'] !== '' ? ucfirst($p['type']) : '');
$owned = $p['owned']; // true | false | null
$priceLabel = '';
if ($p['price'] !== null) {
    $currency = $p['currency'] !== '' ? $p['currency'] . ' ' : '';
    $priceLabel = $currency . number_format((float) $p['price'], 2, ',', '.');
}
$image = $p['banner'] !== '' ? $p['banner'] : $p['image'];
?>
<section class="section" style="padding-top:2rem;">
    <div class="container">
        <p class="mb-2"><a href="/loja">&larr; Voltar à loja</a></p>
        <div class="product-detail">
            <div class="product-detail-media">
                <?php if (!empty($image)): ?>
                    <img src="<?= e($image) ?>" alt="<?= e($p['name']) ?>">
                <?php else: ?>
                    <div class="store-card-noimg" aria-hidden="true">🎮</div>
                <?php endif; ?>
            </div>
            <div class="product-detail-info">
                <div class="store-card-tags">
                    <?php if ($typeLabel !== ''): ?><span class="store-tag"><?= e($typeLabel) ?></span><?php endif; ?>
                    <?php if (!empty($p['rarity'])): ?><span class="store-tag store-rarity"><?= e(ucfirst($p['rarity'])) ?></span><?php endif; ?>
                    <?php if (!empty($p['featured'])): ?><span class="store-badge featured" style="position:static;">Destaque</span><?php endif; ?>
                </div>
                <h1><?= e($p['name']) ?></h1>
                <?php if (!empty($p['description'])): ?>
                    <p class="product-desc"><?= e($p['description']) ?></p>
                <?php endif; ?>
                <?php if ($priceLabel !== ''): ?>
                    <div class="product-price"><?= e($priceLabel) ?></div>
                <?php endif; ?>

                <?php if ($owned === true): ?>
                    <div class="site-alert site-alert-success">Você já possui este item.</div>
                <?php else: ?>
                    <form method="post" action="/loja/comprar" class="product-buy">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
                        <?php if ($loggedIn): ?>
                            <button type="submit" class="btn btn-primary btn-lg">Iniciar pedido</button>
                        <?php else: ?>
                            <a href="/login" class="btn btn-primary btn-lg">Entrar para comprar</a>
                        <?php endif; ?>
                    </form>
                    <p class="muted mt-2" style="font-size:.9rem;">
                        Ao iniciar um pedido, ele fica <strong>pendente</strong>. Não há cobrança real e o item
                        não é concedido até que o pagamento seja confirmado pelo backend do jogo.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
