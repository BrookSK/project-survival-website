<?php
/**
 * @var array $orders
 */
use App\Services\Commerce\OrderPresenter;
$orders = $orders ?? [];
?>
<section class="page-hero">
    <div class="container"><h1>Meus pedidos</h1><p>Acompanhe seus pedidos e entregas.</p></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'orders']) ?>
        <div class="account-content">
            <?php if (empty($orders)): ?>
                <div class="form-card">
                    <p class="muted">Você ainda não tem pedidos.</p>
                    <a href="/loja" class="btn btn-primary mt-2">Ir à loja</a>
                </div>
            <?php else: ?>
                <div class="form-card">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Data</th>
                                <th>Total</th>
                                <th>Pagamento</th>
                                <th>Entrega</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><?= e($o['reference']) ?></td>
                                    <td><?= e(format_date($o['created_at'], 'd/m/Y H:i')) ?></td>
                                    <td><?= e(OrderPresenter::money((int) $o['total_cents'], $o['currency'])) ?></td>
                                    <td><span class="badge badge-<?= e(OrderPresenter::badge($o['payment_status'])) ?>"><?= e(OrderPresenter::paymentLabel($o['payment_status'])) ?></span></td>
                                    <td><span class="badge badge-<?= e(OrderPresenter::badge($o['fulfillment_status'])) ?>"><?= e(OrderPresenter::fulfillmentLabel($o['fulfillment_status'])) ?></span></td>
                                    <td><a href="/conta/pedidos/<?= e(rawurlencode($o['reference'])) ?>" class="btn btn-ghost btn-sm">Detalhes</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
