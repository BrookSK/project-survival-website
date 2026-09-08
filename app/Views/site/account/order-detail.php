<?php
/**
 * @var array $order
 * @var array $items
 * @var array $transactions
 * @var array $fulfillments
 * @var array $timeline
 */
use App\Services\Commerce\OrderPresenter;
$order = $order ?? [];
$items = $items ?? [];
$timeline = $timeline ?? [];
$fulfillments = $fulfillments ?? [];
?>
<section class="page-hero">
    <div class="container"><h1>Pedido <?= e($order['reference']) ?></h1></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'orders']) ?>
        <div class="account-content">
            <p class="mb-2"><a href="/conta/pedidos">&larr; Voltar aos pedidos</a></p>

            <div class="form-card">
                <h2 class="mb-2">Resumo</h2>
                <div class="order-badges" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <span class="badge badge-<?= e(OrderPresenter::badge($order['order_status'])) ?>">Pedido: <?= e(OrderPresenter::orderLabel($order['order_status'])) ?></span>
                    <span class="badge badge-<?= e(OrderPresenter::badge($order['payment_status'])) ?>">Pagamento: <?= e(OrderPresenter::paymentLabel($order['payment_status'])) ?></span>
                    <span class="badge badge-<?= e(OrderPresenter::badge($order['fulfillment_status'])) ?>">Entrega: <?= e(OrderPresenter::fulfillmentLabel($order['fulfillment_status'])) ?></span>
                </div>

                <table class="account-table">
                    <thead><tr><th>Item</th><th>Qtd</th><th>Unitário</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= e($it['name']) ?></td>
                                <td><?= (int) $it['quantity'] ?></td>
                                <td><?= e(OrderPresenter::money((int) $it['unit_price_cents'], $it['currency'])) ?></td>
                                <td><?= e(OrderPresenter::money((int) $it['line_total_cents'], $it['currency'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <dl class="account-data mt-3">
                    <?php if ((int) $order['discount_cents'] > 0): ?>
                        <div><dt>Desconto</dt><dd>- <?= e(OrderPresenter::money((int) $order['discount_cents'], $order['currency'])) ?><?php if (!empty($order['coupon_code'])): ?> (<?= e($order['coupon_code']) ?>)<?php endif; ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Total</dt><dd><strong><?= e(OrderPresenter::money((int) $order['total_cents'], $order['currency'])) ?></strong></dd></div>
                </dl>
            </div>

            <?php if ($order['payment_status'] !== 'approved' && in_array($order['order_status'], ['pending', 'awaiting_payment'], true)): ?>
                <div class="form-card">
                    <p>Este pedido aguarda pagamento.</p>
                    <a href="/checkout/<?= e(rawurlencode($order['reference'])) ?>/pagamento" class="btn btn-primary">Concluir pagamento</a>
                </div>
            <?php endif; ?>

            <?php if ($order['fulfillment_status'] === 'pending' || $order['fulfillment_status'] === 'processing'): ?>
                <div class="site-alert site-alert-info">
                    O pagamento foi confirmado e a entrega do item está sendo processada pela API do jogo.
                    Isso pode levar alguns instantes.
                </div>
            <?php elseif ($order['fulfillment_status'] === 'failed'): ?>
                <div class="site-alert site-alert-error">
                    Houve um problema na entrega. Nossa equipe foi notificada. Se precisar, entre em contato pelo
                    <a href="/contato">suporte</a> informando o número do pedido.
                </div>
            <?php endif; ?>

            <div class="form-card">
                <h2 class="mb-2">Histórico</h2>
                <ul class="order-timeline">
                    <?php foreach ($timeline as $ev): ?>
                        <li>
                            <span class="order-timeline-date"><?= e(format_date($ev['created_at'], 'd/m/Y H:i')) ?></span>
                            <span class="order-timeline-msg"><?= e($ev['message'] ?: $ev['type']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="muted" style="font-size:.85rem;">
                    A entrega dos itens é feita e confirmada pela API do jogo. O site não concede itens diretamente.
                </p>
            </div>
        </div>
    </div>
</section>
