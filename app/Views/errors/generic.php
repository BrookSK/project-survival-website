<?php
/** @var int $status */
$status = $status ?? 500;
$messages = [
    404 => ['Página não encontrada', 'A página que você procura não existe ou foi movida.'],
    403 => ['Acesso negado', 'Você não tem permissão para acessar este recurso.'],
    405 => ['Método não permitido', 'A requisição usou um método HTTP não suportado por esta rota.'],
    419 => ['Sessão expirada', 'Sua sessão expirou. Recarregue a página e tente novamente.'],
    500 => ['Erro interno', 'Ocorreu um erro inesperado. Tente novamente mais tarde.'],
];
[$title, $text] = $messages[$status] ?? $messages[500];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= (int) $status ?> - <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
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
        .code {
            font-size: clamp(5rem, 18vw, 9rem); font-weight: 800; line-height: 1;
            background: linear-gradient(135deg, #4f7cff, #a855f7); -webkit-background-clip: text;
            background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -0.05em;
        }
        h1 { font-size: 1.6rem; margin: 0.5rem 0 0.75rem; }
        p { color: #9aa4bd; line-height: 1.6; margin-bottom: 2rem; }
        a {
            display: inline-block; padding: 0.85rem 1.75rem; border-radius: 999px;
            background: linear-gradient(135deg, #4f7cff, #6d5efc); color: #fff; text-decoration: none;
            font-weight: 600; transition: transform .15s ease, box-shadow .15s ease;
        }
        a:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(79,124,255,.4); }
    </style>
</head>
<body>
    <div class="box">
        <div class="code"><?= (int) $status ?></div>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></p>
        <a href="/">Voltar ao início</a>
    </div>
</body>
</html>
