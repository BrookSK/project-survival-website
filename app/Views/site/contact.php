<?php
/** @var array $errors */
$errors = $errors ?? [];
?>
<section class="page-hero">
    <div class="container">
        <h1>Contato</h1>
        <p>Tem dúvidas ou sugestões? Fale com a nossa equipe.</p>
    </div>
</section>

<section class="section" style="padding-top:1rem;">
    <div class="container">
        <div class="form-card">
            <form method="post" action="/contato" novalidate>
                <?= csrf_field() ?>
                <!-- Honeypot anti-spam (oculto para humanos) -->
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <label>Não preencha<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="form-field">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" value="<?= old('name') ?>" required>
                    <?php if (isset($errors['name'])): ?><div class="form-error"><?= e($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                    <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="subject">Assunto</label>
                    <input type="text" id="subject" name="subject" value="<?= old('subject') ?>" required>
                    <?php if (isset($errors['subject'])): ?><div class="form-error"><?= e($errors['subject']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="message">Mensagem</label>
                    <textarea id="message" name="message" required><?= old('message') ?></textarea>
                    <?php if (isset($errors['message'])): ?><div class="form-error"><?= e($errors['message']) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Enviar mensagem</button>
            </form>
        </div>
    </div>
</section>
