<?php
/**
 * Card de produto da loja. Dados normalizados pelo GameApiAdapter.
 * @var array $product
 */
$p = $product;
$typeLabels = ['skin' => 'Skin', 'bundle' => 'Pacote', 'dlc' => 'DLC', 'boost' => 'Boost'];
$typeLabel = $typeLabels[$p['type']] ?? ($p['type'] !== '' ? ucfirst($p['type']) : '');
$rarity = $p['rarity'] ?? '';
$rarityClass = $rarity !== '' ? 'rarity-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($rarity)) : '';
$owned = $p['owned']; // true | false | null (desconhecido)

// Formata preço respeitando a moeda recebida (não assume BRL).
$priceLabel = '';
if ($p['price'] !== null) {
    $currency = $p['currency'] !== '' ? $p['currency'] . ' ' : '';
    $priceLabel = $currency . number_format((float) $p['price'], 2, ',', '.');
}
$link = '/loja/produto/' . rawurlencode($p['slug'] !== '' ? $p['slug'] : $p['id']);
?>
<article class="store-card <?= e($rarityClass) ?>">
    <a href="<?= e($link) ?>" class="store-card-media">
        <?php if (!empty($p['image'])): ?>
            <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <?php else: ?>
            <div class="store-card-noimg" aria-hidden="true">🎮</div>
        <?php endif; ?>
        <?php if (!empty($p['featured'])): ?><span class="store-badge featured">Destaque</span><?php endif; ?>
        <?php if ($owned === true): ?><span class="store-badge owned">Você possui</span><?php endif; ?>
    </a>
    <div class="store-card-body">
        <div class="store-card-tags">
            <?php if ($typeLabel !== ''): ?><span class="store-tag"><?= e($typeLabel) ?></span><?php endif; ?>
            <?php if ($rarity !== ''): ?><span class="store-tag store-rarity"><?= e(ucfirst($rarity)) ?></span><?php endif; ?>
        </div>
        <h3 class="store-card-title"><a href="<?= e($link) ?>"><?= e($p['name']) ?></a></h3>
        <?php if (!empty($p['description'])): ?>
            <p class="store-card-desc"><?= e(excerpt((string) $p['description'], 90)) ?></p>
        <?php endif; ?>
        <div class="store-card-foot">
            <?php if ($priceLabel !== ''): ?><span class="store-price"><?= e($priceLabel) ?></span><?php endif; ?>
            <?php if ($owned === true): ?>
                <span class="store-owned-label">Já possui</span>
            <?php else: ?>
                <a href="<?= e($link) ?>" class="btn btn-ghost btn-sm">Ver</a>
            <?php endif; ?>
        </div>
    </div>
</article>
