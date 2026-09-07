<?php
/**
 * @var string $title
 * @var string $policyUrl
 * @var bool $hasPolicy
 */
?>
<section class="page-hero">
    <div class="container"><h1>Preferências de Cookies</h1><p>Gerencie como o site utiliza cookies.</p></div>
</section>

<section class="section" style="padding-top:1.5rem;">
    <div class="container" style="max-width:760px;">
        <div class="form-card mb-3">
            <h2 class="mb-2">Categorias de cookies</h2>
            <div class="cookie-cat">
                <div>
                    <strong>Essenciais</strong>
                    <p class="muted">Necessários para o funcionamento do site (sessão, autenticação e proteção contra fraude/CSRF). Sempre ativos.</p>
                </div>
                <span class="badge-fixed">Sempre ativos</span>
            </div>
            <div class="cookie-cat">
                <div>
                    <strong>Opcionais</strong>
                    <p class="muted">
                        Usados apenas se você aceitar. No momento, o site <strong>não utiliza cookies de
                        análise, marketing ou de terceiros</strong>. Caso passem a existir, só serão
                        carregados após o seu consentimento.
                    </p>
                </div>
                <span id="cookieState" class="badge-fixed">—</span>
            </div>

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem;">
                <button type="button" class="btn btn-primary" data-cookie-choice="accepted">Aceitar opcionais</button>
                <button type="button" class="btn btn-ghost" data-cookie-choice="rejected">Recusar opcionais</button>
            </div>
            <p class="muted mt-3" style="font-size:.88rem;">
                Sua escolha fica salva neste navegador. <?php if ($hasPolicy): ?>Leia a <a href="<?= e($policyUrl) ?>">Política de Cookies</a>.<?php endif; ?>
            </p>
        </div>
    </div>
</section>

<script>
(function () {
    var stateEl = document.getElementById('cookieState');
    function refresh() {
        if (!window.cookieConsent || !stateEl) return;
        var c = window.cookieConsent.get();
        stateEl.textContent = c && c.choice === 'accepted' ? 'Aceitos'
            : (c && c.choice === 'rejected' ? 'Recusados' : 'Não definido');
    }
    document.querySelectorAll('[data-cookie-choice]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (window.cookieConsent) { window.cookieConsent.set(btn.getAttribute('data-cookie-choice')); }
            var b = document.getElementById('cookieBanner'); if (b) b.hidden = true;
            refresh();
        });
    });
    refresh();
})();
</script>
