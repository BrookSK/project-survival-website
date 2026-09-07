<?php
$siteName = setting('site_name', 'Site');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Em manutenção · <?= e($siteName) ?></title>
    <style>
        :root { color-scheme: dark; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 50% 0%, #14213d 0%, #0a0f1e 60%, #05070f 100%);
            color: #e8ecf5; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 2rem; text-align: center;
        }
        .box { max-width: 520px; }
        .icon { font-size: 4rem; margin-bottom: 1.5rem; }
        h1 { font-size: 1.8rem; margin-bottom: 1rem; }
        p { color: #9aa4bd; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔧</div>
        <h1><?= e($siteName) ?></h1>
        <p>Estamos realizando uma manutenção rápida para melhorar sua experiência. Voltamos em breve!</p>
    </div>
</body>
</html>
