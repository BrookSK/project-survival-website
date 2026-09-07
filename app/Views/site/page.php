<?php
/** @var array $page */
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($page['title']) ?></h1>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <div class="prose" style="margin:0 auto;">
            <?= $page['content'] // Conteúdo HTML gerenciado no painel ?>
        </div>
    </div>
</section>
