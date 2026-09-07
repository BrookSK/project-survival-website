<?php
/**
 * Navegação lateral da área "Minha conta".
 * @var string $active  chave do item ativo
 */
$active = $active ?? '';
$items = [
    'overview'  => ['/conta', 'Visão geral'],
    'inventory' => ['/conta/inventario', 'Inventário'],
    'redeem'    => ['/conta/resgatar', 'Resgatar código'],
    'store'     => ['/loja', 'Loja'],
    'security'  => ['/conta/seguranca', 'Segurança'],
    'privacy'   => ['/conta/privacidade', 'Privacidade'],
];
?>
<nav class="account-nav" aria-label="Menu da conta">
    <?php foreach ($items as $key => [$url, $label]): ?>
        <a href="<?= e($url) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <form method="post" action="/logout">
        <?= csrf_field() ?>
        <button type="submit" class="account-nav-logout">Sair</button>
    </form>
</nav>
