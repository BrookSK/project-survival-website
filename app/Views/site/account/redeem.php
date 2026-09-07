<?php
/** @var array $errors */
$errors = $errors ?? [];
?>
<section class="page-hero">
    <div class="container"><h1>Resgatar código</h1><p>Insira um código promocional para resgatar recompensas.</p></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'redeem']) ?>
        <div class="account-content">
            <div class="form-card" style="max-width:480px;">
                <form method="post" action="/conta/resgatar" novalidate id="redeemForm">
                    <?= csrf_field() ?>
                    <div class="form-field">
                        <label for="code">Código</label>
                        <input type="text" id="code" name="code" value="<?= old('code') ?>"
                               placeholder="Ex.: SURVIVAL2026" autocomplete="off" autocapitalize="characters"
                               spellcheck="false" required autofocus maxlength="64">
                        <?php if (isset($errors['code'])): ?><div class="form-error"><?= e($errors['code']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;" id="redeemBtn">Resgatar</button>
                </form>
                <p class="muted mt-3" style="font-size:.88rem;">
                    O resgate é processado pelo servidor do jogo. Códigos podem estar
                    expirados, esgotados ou já utilizados. Evite múltiplos envios.
                </p>
            </div>
        </div>
    </div>
</section>

<script>
// Evita duplo envio (a API também aplica rate limit no resgate).
(function () {
    var form = document.getElementById('redeemForm');
    var btn = document.getElementById('redeemBtn');
    if (!form || !btn) return;
    form.addEventListener('submit', function () {
        btn.setAttribute('data-loading', 'true');
        btn.disabled = true;
        btn.textContent = 'Resgatando...';
    });
})();
</script>
