<?php
/**
 * @var array $events
 * @var bool $offline
 */
?>
<section class="page-hero">
    <div class="container">
        <h1>Eventos</h1>
        <p>Acompanhe os eventos ativos do Project Survival.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <?php if ($offline && empty($events)): ?>
            <div class="site-alert site-alert-error">Não foi possível carregar os eventos agora. Tente novamente em instantes.</div>
        <?php elseif (empty($events)): ?>
            <div class="empty-state"><p>Nenhum evento ativo no momento.</p></div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $ev): ?>
                    <article class="event-card">
                        <?php if (!empty($ev['image'])): ?>
                            <div class="event-media"><img src="<?= e($ev['image']) ?>" alt="<?= e($ev['title']) ?>" loading="lazy"></div>
                        <?php endif; ?>
                        <div class="event-body">
                            <h3><?= e($ev['title']) ?></h3>
                            <?php if (!empty($ev['description'])): ?>
                                <p><?= e(excerpt((string) $ev['description'], 160)) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($ev['ends_at'])): ?>
                                <p class="event-meta">Termina em <?= e(format_date($ev['ends_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
