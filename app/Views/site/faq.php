<?php
/** @var array $groups */
?>
<section class="page-hero">
    <div class="container">
        <h1>Perguntas Frequentes</h1>
        <p>Encontre respostas para as dúvidas mais comuns.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container" style="max-width:820px;">
        <?php if (empty($groups)): ?>
            <p class="text-center" style="color:var(--text-muted);padding:3rem 0;">Nenhuma pergunta cadastrada ainda.</p>
        <?php else: ?>
            <?php foreach ($groups as $groupName => $faqs): ?>
                <div class="faq-group">
                    <?php if ($groupName !== 'Geral' || count($groups) > 1): ?>
                        <h3><?= e($groupName) ?></h3>
                    <?php endif; ?>
                    <?php foreach ($faqs as $faq): ?>
                        <div class="faq-item">
                            <button class="faq-question" aria-expanded="false">
                                <span><?= e($faq['question']) ?></span>
                                <span class="chev">▾</span>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-inner"><?= $faq['answer'] // HTML gerenciado no painel ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
