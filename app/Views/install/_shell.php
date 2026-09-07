<?php
/**
 * Shell do instalador. Recebe $title e $slot (HTML).
 * @var string $title
 * @var string $slot
 */
$flashes = \App\Core\Session::getFlashes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Instalação') ?></title>
    <style>
        :root { color-scheme: dark; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 30% 0%, #16234a 0%, #0a0f1e 55%, #05070f 100%);
            color: #e8ecf5; min-height: 100vh; padding: 2.5rem 1.5rem;
        }
        .wrap { max-width: 720px; margin: 0 auto; }
        .card {
            background: rgba(19,26,46,.85); border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px; padding: 2.5rem; margin-bottom: 1.5rem; backdrop-filter: blur(10px);
        }
        h1 { font-size: 1.8rem; margin-bottom: .5rem; }
        h2 { font-size: 1.2rem; margin-bottom: 1rem; }
        .lead { color: #9aa4bd; margin-bottom: 2rem; }
        .check { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.06); }
        .check:last-child { border-bottom: none; }
        .check .status { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
        .status.ok { background: rgba(34,197,94,.2); color: #86efac; }
        .status.fail { background: rgba(239,68,68,.2); color: #fca5a5; }
        .check .detail { margin-left: auto; color: #6b7590; font-size: .85rem; }
        label { display: block; margin-bottom: .4rem; font-size: .88rem; font-weight: 500; color: #c3cadd; }
        .field { margin-bottom: 1.2rem; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        input {
            width: 100%; padding: .8rem 1rem; border-radius: 9px; border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04); color: #fff; font-size: .95rem;
        }
        input:focus { outline: none; border-color: #4f7cff; box-shadow: 0 0 0 3px rgba(79,124,255,.25); }
        .btn {
            display: inline-block; padding: .9rem 2rem; border: none; border-radius: 999px; cursor: pointer;
            background: linear-gradient(135deg, #4f7cff, #6d5efc); color: #fff; font-weight: 600; font-size: 1rem;
        }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-block { width: 100%; }
        .alert { padding: .85rem 1rem; border-radius: 9px; margin-bottom: 1.5rem; font-size: .9rem; }
        .alert-error { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.35); color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.35); color: #86efac; }
        .field-error { color: #fca5a5; font-size: .8rem; margin-top: .3rem; }
        a { color: #7f9cff; }
        @media (max-width: 560px) { .row { grid-template-columns: 1fr; } .card { padding: 1.5rem; } }
    </style>
</head>
<body>
    <div class="wrap">
        <?php foreach ($flashes['error'] ?? [] as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; ?>
        <?php foreach ($flashes['success'] ?? [] as $m): ?><div class="alert alert-success"><?= e($m) ?></div><?php endforeach; ?>
        <?= $slot ?>
    </div>
</body>
</html>
