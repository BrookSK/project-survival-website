<?php
/**
 * Shell reutilizável para páginas de autenticação (login, recuperação, reset).
 * Recebe: $title (string), $slot (string HTML do miolo do card).
 * Exibe flash messages e é totalmente responsivo.
 *
 * @var string $title
 * @var string $slot
 */
$flashes = \App\Core\Session::getFlashes();
$siteName = setting('site_name', 'Painel Administrativo');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · <?= e($siteName) ?></title>
    <style>
        :root { color-scheme: dark; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 20% 10%, #1b2a4a 0%, #0a0f1e 55%, #05070f 100%);
            color: #e8ecf5; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1.5rem;
        }
        .card {
            width: 100%; max-width: 420px;
            background: rgba(19, 26, 46, 0.85); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px; padding: 2.5rem; backdrop-filter: blur(12px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
        }
        .brand { text-align: center; margin-bottom: 2rem; }
        .brand-logo {
            width: 56px; height: 56px; border-radius: 14px; margin: 0 auto 1rem;
            background: linear-gradient(135deg, #4f7cff, #a855f7);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; font-weight: 800; color: #fff;
        }
        .brand h1 { font-size: 1.25rem; font-weight: 700; }
        .brand p { color: #8b95b0; font-size: 0.9rem; margin-top: 0.35rem; }
        label { display: block; font-size: 0.85rem; color: #c3cadd; margin-bottom: 0.4rem; font-weight: 500; }
        .field { margin-bottom: 1.25rem; }
        input[type=email], input[type=password], input[type=text] {
            width: 100%; padding: 0.85rem 1rem; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.04);
            color: #fff; font-size: 0.95rem; transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            outline: none; border-color: #4f7cff; box-shadow: 0 0 0 3px rgba(79,124,255,0.25);
        }
        .btn {
            width: 100%; padding: 0.9rem; border: none; border-radius: 10px; cursor: pointer;
            background: linear-gradient(135deg, #4f7cff, #6d5efc); color: #fff;
            font-size: 0.95rem; font-weight: 600; transition: transform .15s, box-shadow .15s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(79,124,255,0.4); }
        .links { text-align: center; margin-top: 1.5rem; font-size: 0.88rem; }
        .links a { color: #7f9cff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .alert { padding: 0.8rem 1rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.35); color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.35); color: #86efac; }
        .field-error { color: #fca5a5; font-size: 0.8rem; margin-top: 0.35rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <div class="brand-logo"><?= e(str_sub($siteName, 0, 1)) ?></div>
            <h1><?= e($siteName) ?></h1>
            <p>Painel Administrativo</p>
        </div>

        <?php foreach ($flashes['error'] ?? [] as $msg): ?>
            <div class="alert alert-error"><?= e($msg) ?></div>
        <?php endforeach; ?>
        <?php foreach ($flashes['success'] ?? [] as $msg): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endforeach; ?>

        <?= $slot ?>
    </div>
</body>
</html>
