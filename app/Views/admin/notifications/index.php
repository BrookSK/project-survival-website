<?php
/** @var array $notifications */
$icons = ['success' => '✅', 'warning' => '⚠️', 'error' => '❌', 'message' => '✉️', 'info' => 'ℹ️'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Notificações</h1>
        <p class="page-subtitle">Atividades e alertas do sistema.</p>
    </div>
</div>

<div class="card">
    <?php if (empty($notifications)): ?>
        <div class="empty-state"><div class="icon">🔔</div><p>Nenhuma notificação.</p></div>
    <?php else: ?>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:.5rem;">
            <?php foreach ($notifications as $n): ?>
                <li style="display:flex;gap:.75rem;align-items:flex-start;padding:.85rem 1rem;background:var(--bg-elev-2);border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <span style="font-size:1.2rem;"><?= $icons[$n['type']] ?? 'ℹ️' ?></span>
                    <div style="flex:1;">
                        <?php if (!empty($n['url'])): ?>
                            <a href="<?= e($n['url']) ?>" style="font-weight:600;color:var(--text);"><?= e($n['title']) ?></a>
                        <?php else: ?>
                            <strong><?= e($n['title']) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($n['message'])): ?><div class="muted text-sm"><?= e($n['message']) ?></div><?php endif; ?>
                        <div class="muted" style="font-size:.75rem;"><?= e(format_date($n['created_at'])) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
