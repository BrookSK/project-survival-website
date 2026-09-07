<?php
/**
 * @var array $me
 * @var array $consents
 * @var bool $marketing
 * @var array|null $latestExport
 */
$me = $me ?? [];
$exportToken = \App\Core\Session::get('__last_export_token');
\App\Core\Session::remove('__last_export_token');
$mask = function (?string $email): string {
    if (!$email || strpos($email, '@') === false) { return $email ?: '—'; }
    [$u, $d] = explode('@', $email, 2);
    $u = strlen($u) <= 2 ? $u : substr($u, 0, 2) . str_repeat('*', min(6, max(1, strlen($u) - 2)));
    return $u . '@' . $d;
};
?>
<section class="page-hero">
    <div class="container"><h1>Privacidade</h1><p>Seus dados, consentimentos e direitos.</p></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'privacy']) ?>
        <div class="account-content">

            <!-- Meus dados -->
            <div class="form-card mb-3">
                <h2 class="mb-2">Meus dados</h2>
                <dl class="account-data">
                    <?php if (!empty($me['username'])): ?><div><dt>Usuário</dt><dd><?= e($me['username']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($me['email'])): ?><div><dt>E-mail</dt><dd><?= e($me['email']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($me['id'])): ?><div><dt>ID do jogador</dt><dd><?= e((string) $me['id']) ?></dd></div><?php endif; ?>
                </dl>
                <p class="muted mt-2" style="font-size:.88rem;">
                    Sua conta é gerida pela API do jogo. Itens, inventário e histórico de jogo são fornecidos por ela.
                    O website guarda seus consentimentos e solicitações de privacidade.
                </p>
            </div>

            <!-- Consentimentos -->
            <div class="form-card mb-3">
                <h2 class="mb-2">Consentimentos</h2>
                <form method="post" action="/conta/privacidade/consentimentos">
                    <?= csrf_field() ?>
                    <label class="checkbox-row" style="display:flex;gap:.6rem;align-items:center;">
                        <input type="checkbox" name="marketing" value="1" <?= $marketing ? 'checked' : '' ?>>
                        Quero receber novidades e promoções por e-mail (opcional)
                    </label>
                    <p class="muted mt-2" style="font-size:.85rem;">
                        Comunicações essenciais (segurança, recuperação de conta) não dependem deste consentimento.
                    </p>
                    <button type="submit" class="btn btn-primary mt-2">Salvar preferências</button>
                </form>
            </div>

            <!-- Exportar dados -->
            <div class="form-card mb-3">
                <h2 class="mb-2">Baixar meus dados</h2>
                <p class="muted mb-2" style="font-size:.9rem;">Gere um arquivo JSON com os dados que o site guarda sobre você (sem senhas ou tokens).</p>
                <?php if ($exportToken): ?>
                    <div class="site-alert site-alert-success">
                        Sua exportação está pronta.
                        <a href="/conta/privacidade/exportar/download?token=<?= e($exportToken) ?>">Baixar agora</a>.
                    </div>
                <?php endif; ?>
                <form method="post" action="/conta/privacidade/exportar">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost">Gerar exportação</button>
                </form>
            </div>

            <!-- Excluir conta -->
            <div class="form-card">
                <h2 class="mb-2">Excluir minha conta</h2>
                <p class="muted mb-2" style="font-size:.9rem;">
                    A exclusão é analisada pela nossa equipe. Alguns dados podem precisar ser mantidos ou anonimizados
                    para cumprir obrigações legais e operacionais. Sua conta do jogo é encerrada conforme o processo aplicável.
                </p>
                <form method="post" action="/conta/privacidade/excluir" id="deleteForm">
                    <?= csrf_field() ?>
                    <div class="form-field">
                        <label for="confirm">Digite <strong>EXCLUIR</strong> para confirmar</label>
                        <input type="text" id="confirm" name="confirm" autocomplete="off" placeholder="EXCLUIR">
                    </div>
                    <div class="form-field">
                        <label for="message">Motivo (opcional)</label>
                        <textarea id="message" name="message" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Solicitar exclusão</button>
                </form>
            </div>

        </div>
    </div>
</section>
