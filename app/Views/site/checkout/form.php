<?php
/**
 * Resumo do checkout. Preço/moeda vêm da Game API (server-side) — o navegador
 * apenas envia product_id, quantidade, método e (opcional) token do cartão.
 *
 * @var array $product        ['product_id','name','unit_price_cents','currency',...]
 * @var int $quantity
 * @var string $publicKey
 * @var string $gateway
 * @var string $currency
 * @var array|null $purchaseTerms  ['policy'=>..., 'version'=>...]
 * @var array|null $refundTerms
 */
$unit = (int) $product['unit_price_cents'];
$total = $unit * $quantity;
$fmt = static fn (int $cents) => number_format($cents / 100, 2, ',', '.');
?>
<section class="section" style="padding-top:2rem;">
    <div class="container">
        <p class="mb-2"><a href="/loja">&larr; Voltar à loja</a></p>
        <h1 class="mb-3">Finalizar compra</h1>

        <?php if (!empty($_SESSION['__flash']['error'] ?? null)): ?>
            <div class="site-alert site-alert-error"><?= e($_SESSION['__flash']['error']) ?></div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="form-card">
                <h2 class="mb-2">Resumo</h2>
                <dl class="account-data">
                    <div><dt>Produto</dt><dd><?= e($product['name']) ?></dd></div>
                    <div><dt>Preço unitário</dt><dd><?= e($currency) ?> <?= e($fmt($unit)) ?></dd></div>
                    <div><dt>Quantidade</dt><dd><?= (int) $quantity ?></dd></div>
                    <div><dt>Total</dt><dd><strong><?= e($currency) ?> <?= e($fmt($total)) ?></strong></dd></div>
                </dl>
                <p class="muted" style="font-size:.85rem;">
                    O preço é confirmado pelo servidor no momento do pedido. Valores exibidos no
                    navegador não são aceitos como fonte de verdade.
                </p>
            </div>

            <div class="form-card">
                <form method="post" action="/checkout" class="checkout-form" id="checkout-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= e($product['product_id']) ?>">
                    <input type="hidden" name="quantity" value="<?= (int) $quantity ?>">

                    <div class="form-field">
                        <label for="method">Forma de pagamento</label>
                        <select name="method" id="method">
                            <option value="pix">PIX</option>
                            <option value="credit_card">Cartão de crédito</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="coupon">Cupom (opcional)</label>
                        <input type="text" name="coupon" id="coupon" maxlength="40" placeholder="Código do cupom">
                    </div>

                    <?php // Campos do cartão são tokenizados no navegador pelo SDK do gateway. ?>
                    <div id="card-fields" hidden>
                        <input type="hidden" name="card_token" id="card_token" value="">
                        <input type="hidden" name="payment_method_id" id="payment_method_id" value="">
                        <p class="muted" style="font-size:.85rem;">
                            Os dados do cartão são processados diretamente pelo gateway. O site não
                            recebe nem armazena o número do cartão.
                        </p>
                    </div>

                    <div class="form-field form-checkbox">
                        <label>
                            <input type="checkbox" name="accept_terms" value="1" required>
                            Li e aceito os
                            <?php if ($purchaseTerms): ?>
                                <a href="/termos-de-compra" target="_blank" rel="noopener">Termos de Compra</a>
                                (v<?= e($purchaseTerms['version']['version'] ?? '') ?>)
                            <?php else: ?>
                                Termos de Compra
                            <?php endif; ?>
                            <?php if ($refundTerms): ?>
                                e a <a href="/reembolso" target="_blank" rel="noopener">Política de Reembolso</a>
                            <?php endif; ?>.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Continuar para pagamento</button>
                </form>
            </div>
        </div>
    </div>
</section>
<script>
// Alterna os campos de cartão conforme o método (tokenização fica com o gateway).
(function () {
    var method = document.getElementById('method');
    var card = document.getElementById('card-fields');
    if (!method || !card) { return; }
    function toggle() { card.hidden = method.value !== 'credit_card'; }
    method.addEventListener('change', toggle);
    toggle();
})();
</script>
