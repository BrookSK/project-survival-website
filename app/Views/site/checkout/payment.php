<?php
/**
 * Instruções de pagamento. O estado exibido é consultado no backend (polling
 * em /checkout/{ref}/status), que por sua vez consulta o gateway. A tela NUNCA
 * confirma pagamento sozinha.
 *
 * @var array $order
 * @var array|null $transaction
 */
$tx = $transaction ?? [];
$raw = [];
if (!empty($tx['raw_snapshot'])) {
    $decoded = json_decode((string) $tx['raw_snapshot'], true);
    $raw = is_array($decoded) ? $decoded : [];
}
$poi = $raw['point_of_interaction']['transaction_data'] ?? [];
$pixCode = $poi['qr_code'] ?? null;
$pixBase64 = $poi['qr_code_base64'] ?? null;
$ticketUrl = $poi['ticket_url'] ?? null;
?>
<section class="section" style="padding-top:2rem;">
    <div class="container container-narrow">
        <h1 class="mb-2">Pagamento do pedido <?= e($order['reference']) ?></h1>

        <div class="site-alert site-alert-info" id="status-banner" data-status="<?= e($order['payment_status']) ?>">
            Aguardando confirmação do pagamento. Esta página é atualizada automaticamente.
        </div>

        <?php if ($pixBase64): ?>
            <div class="form-card text-center">
                <h2 class="mb-2">Pague com PIX</h2>
                <img src="data:image/png;base64,<?= e($pixBase64) ?>" alt="QR Code PIX" style="max-width:240px;margin:0 auto;">
                <?php if ($pixCode): ?>
                    <div class="form-field mt-2">
                        <label for="pix-code">PIX copia e cola</label>
                        <textarea id="pix-code" rows="3" readonly><?= e($pixCode) ?></textarea>
                        <button type="button" class="btn btn-ghost btn-sm mt-1" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('pix-code').value)">Copiar código</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($ticketUrl): ?>
            <div class="form-card text-center">
                <p>Conclua o pagamento na página segura do gateway.</p>
                <a href="<?= e($ticketUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary">Ir para o pagamento</a>
            </div>
        <?php else: ?>
            <div class="form-card">
                <p>Seu pedido foi registrado. Assim que o pagamento for confirmado, a entrega será
                processada automaticamente.</p>
            </div>
        <?php endif; ?>

        <p class="mt-3">
            <a href="/conta/pedidos/<?= e(rawurlencode($order['reference'])) ?>" class="btn btn-ghost">Ver detalhes do pedido</a>
        </p>
        <p class="muted" style="font-size:.85rem;">
            O item só é entregue após a confirmação real do pagamento pelo gateway e a concessão
            pela API do jogo. Não fechamos a compra com base nesta tela.
        </p>
    </div>
</section>
<script>
// Polling do estado REAL (backend consulta o gateway). Não confirma sozinho.
(function () {
    var ref = <?= json_encode($order['reference']) ?>;
    var banner = document.getElementById('status-banner');
    if (!banner) { return; }
    var tries = 0;
    function poll() {
        if (tries++ > 60) { return; } // ~5 min
        fetch('/checkout/' + encodeURIComponent(ref) + '/status', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) { return; }
                if (d.payment_status === 'approved') {
                    banner.className = 'site-alert site-alert-success';
                    banner.textContent = 'Pagamento aprovado! A entrega está sendo processada.';
                    return;
                }
                if (d.payment_status === 'rejected' || d.payment_status === 'cancelled') {
                    banner.className = 'site-alert site-alert-error';
                    banner.textContent = 'O pagamento não foi concluído.';
                    return;
                }
                setTimeout(poll, 5000);
            })
            .catch(function () { setTimeout(poll, 8000); });
    }
    setTimeout(poll, 5000);
})();
</script>
