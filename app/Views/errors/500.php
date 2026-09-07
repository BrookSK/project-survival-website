<?php
/** @var int $status */
/** @var bool $debug */
/** @var \Throwable|null $exception */
$status = 500;
$debug = $debug ?? false;

if (!empty($debug) && isset($exception) && $exception instanceof \Throwable):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erro (modo debug)</title>
    <style>
        body { font-family: ui-monospace, "Cascadia Code", Consolas, monospace; background: #0d1117; color: #e6edf3; margin: 0; padding: 2rem; }
        h1 { color: #ff7b72; }
        .meta { color: #8b949e; margin-bottom: 1rem; }
        pre { background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 1rem; overflow: auto; line-height: 1.5; }
        .msg { font-size: 1.1rem; color: #ffa657; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="msg"><?= htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="meta"><?= htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') ?>:<?= (int) $exception->getLine() ?></div>
    <pre><?= htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') ?></pre>
</body>
</html>
<?php else:
    include __DIR__ . '/generic.php';
endif;
