<?php

/**
 * Runner de testes de unidade.
 *
 * Uso: php tests/run.php
 *
 * Descobre todos os arquivos tests/unit/*Test.php, instancia as classes que
 * estendem TestCase e executa seus métodos test*. Sai com código 1 se houver
 * qualquer falha (útil para CI).
 */

require_once __DIR__ . '/bootstrap.php';

$dir = __DIR__ . '/unit';
$files = glob($dir . '/*Test.php') ?: [];

$totalPassed = 0;
$totalFailed = 0;
$totalAssertions = 0;
$allFailures = [];

foreach ($files as $file) {
    require_once $file;
}

// Descobre classes de teste declaradas.
foreach (get_declared_classes() as $class) {
    if (!is_subclass_of($class, 'TestCase')) {
        continue;
    }
    /** @var TestCase $instance */
    $instance = new $class();
    $result = $instance->run();

    $totalPassed += $result['passed'];
    $totalFailed += $result['failed'];
    $totalAssertions += $result['assertions'];
    $allFailures = array_merge($allFailures, $result['failures']);

    $status = $result['failed'] === 0 ? 'OK  ' : 'FAIL';
    echo sprintf(
        "[%s] %s — %d passou, %d falhou\n",
        $status,
        $result['class'],
        $result['passed'],
        $result['failed']
    );
}

echo str_repeat('-', 60) . "\n";
echo sprintf(
    "Total: %d testes OK, %d falhas, %d asserções.\n",
    $totalPassed,
    $totalFailed,
    $totalAssertions
);

if ($allFailures) {
    echo "\nFalhas:\n";
    foreach ($allFailures as $f) {
        echo '  - ' . $f . "\n";
    }
    exit(1);
}

exit(0);
