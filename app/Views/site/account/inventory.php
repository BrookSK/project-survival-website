<?php
/**
 * @var array $entitlements  string[] (fonte de verdade do que o jogador possui)
 * @var array $owned         itens possuídos (formato conforme a API)
 * @var array $orders        pedidos (formato conforme a API)
 * @var string|null $error
 */
$entitlements = $entitlements ?? [];
$owned = $owned ?? [];
$orders = $orders ?? [];

/** Torna um identificador técnico mais legível (ex.: "skin_hero" -> "Skin Hero"). */
$pretty = function ($value): string {
    if (is_array($value)) {
        $value = $value['name'] ?? ($value['title'] ?? ($value['id'] ?? ($value['sku'] ?? '')));
    }
    $value = (string) $value;
    return $value === '' ? '-' : ucwords(str_replace(['_', '-', '.'], ' ', $value));
};
$orderStatusLabels = ['pending' => 'Pendente', 'paid' => 'Pago', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado', 'completed' => 'Concluído'];
?>
<section class="page-hero">
    <div class="container"><h1>Inventário</h1><p>O que você possui no Project Survival.</p></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'inventory']) ?>
        <div class="account-content">
            <?php if ($error): ?>
                <div class="site-alert site-alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="form-card mb-3">
                <h2 class="mb-2">Itens que você possui</h2>
                <?php
                // Preferimos a lista consolidada; se vazia, usamos entitlements.
                $items = !empty($owned) ? $owned : $entitlements;
                ?>
                <?php if (empty($items)): ?>
                    <p class="muted">Você ainda não possui itens.</p>
                <?php else: ?>
                    <ul class="entitlement-list">
                        <?php foreach ($items as $it): ?>
                            <li><span class="entitlement-dot" aria-hidden="true"></span><?= e($pretty($it)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="form-card">
                <h2 class="mb-2">Pedidos</h2>
                <?php if (empty($orders)): ?>
                    <p class="muted">Nenhum pedido encontrado.</p>
                <?php else: ?>
                    <table class="account-table">
                        <thead><tr><th>Pedido</th><th>Item</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <?php
                                $oid = is_array($o) ? ($o['order_id'] ?? ($o['id'] ?? '-')) : (string) $o;
                                $oitem = is_array($o) ? ($o['product'] ?? ($o['product_id'] ?? ($o['item'] ?? '-'))) : '-';
                                $ostatus = is_array($o) ? (string) ($o['status'] ?? '') : '';
                                $statusLabel = $orderStatusLabels[$ostatus] ?? ($ostatus !== '' ? ucfirst($ostatus) : '-');
                                ?>
                                <tr>
                                    <td><?= e((string) $oid) ?></td>
                                    <td><?= e($pretty($oitem)) ?></td>
                                    <td><span class="order-status status-<?= e($ostatus ?: 'unknown') ?>"><?= e($statusLabel) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="muted mt-2" style="font-size:.85rem;">Pedidos <strong>pendentes</strong> não representam cobrança nem concessão do item.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
