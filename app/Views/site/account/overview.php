<?php
/** @var array $me */
$me = $me ?? [];
$username = $me['username'] ?? ($me['name'] ?? 'Jogador');
$email = $me['email'] ?? '';
?>
<section class="page-hero">
    <div class="container"><h1>Minha conta</h1><p>Olá, <?= e($username) ?>!</p></div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container account-layout">
        <?= partial('site.account._nav', ['active' => 'overview']) ?>
        <div class="account-content">
            <div class="form-card">
                <h2 class="mb-2">Perfil</h2>
                <dl class="account-data">
                    <div><dt>Usuário</dt><dd><?= e($username) ?></dd></div>
                    <?php if ($email !== ''): ?><div><dt>E-mail</dt><dd><?= e($email) ?></dd></div><?php endif; ?>
                    <?php if (!empty($me['id'])): ?><div><dt>ID</dt><dd><?= e((string) $me['id']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($me['created_at'])): ?><div><dt>Desde</dt><dd><?= e(format_date($me['created_at'], 'd/m/Y')) ?></dd></div><?php endif; ?>
                </dl>
                <div class="mt-3" style="display:flex;gap:.6rem;flex-wrap:wrap;">
                    <a href="/conta/inventario" class="btn btn-ghost">Ver inventário</a>
                    <a href="/conta/resgatar" class="btn btn-primary">Resgatar código</a>
                </div>
            </div>
        </div>
    </div>
</section>
