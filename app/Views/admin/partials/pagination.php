<?php
/**
 * Componente de paginação reutilizável.
 *
 * @var int    $page      Página atual (1-based)
 * @var int    $total     Total de itens
 * @var int    $perPage   Itens por página
 * @var string $baseUrl   URL base (sem query de página)
 * @var array  $query     Parâmetros extras a preservar na querystring (ex.: busca)
 */
$page = max(1, (int) ($page ?? 1));
$total = (int) ($total ?? 0);
$perPage = max(1, (int) ($perPage ?? 15));
$baseUrl = $baseUrl ?? '';
$query = $query ?? [];

$totalPages = (int) ceil($total / $perPage);
if ($totalPages <= 1) {
    return;
}

$buildUrl = function (int $p) use ($baseUrl, $query): string {
    $params = array_merge($query, ['page' => $p]);
    return $baseUrl . '?' . http_build_query($params);
};

$start = max(1, $page - 2);
$end = min($totalPages, $page + 2);
?>
<nav class="pagination" aria-label="Paginação">
    <?php if ($page > 1): ?>
        <a href="<?= e($buildUrl($page - 1)) ?>">‹ Anterior</a>
    <?php else: ?>
        <span class="disabled">‹ Anterior</span>
    <?php endif; ?>

    <?php if ($start > 1): ?>
        <a href="<?= e($buildUrl(1)) ?>">1</a>
        <?php if ($start > 2): ?><span class="disabled">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $page): ?>
            <span class="current"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($buildUrl($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="disabled">…</span><?php endif; ?>
        <a href="<?= e($buildUrl($totalPages)) ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
        <a href="<?= e($buildUrl($page + 1)) ?>">Próxima ›</a>
    <?php else: ?>
        <span class="disabled">Próxima ›</span>
    <?php endif; ?>
</nav>
