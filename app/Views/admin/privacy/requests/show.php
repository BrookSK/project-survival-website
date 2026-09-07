<?php
/**
 * @var array $req
 * @var array $statuses
 */
$statusLabels = [
    'pending' => 'Pendente', 'in_review' => 'Em análise', 'awaiting_user' => 'Aguardando titular',
    'completed' => 'Concluída', 'rejected' => 'Rejeitada', 'cancelled' => 'Cancelada',
];
$typeLabels = [
    'access' => 'Acesso', 'correction' => 'Correção', 'deletion' => 'Exclusão',
    'portability' => 'Portabilidade', 'consent_revocation' => 'Revogação de consentimento',
    'information' => 'Informação', 'other' => 'Outro',
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Solicitação <code><?= e($req['public_id']) ?></code></h1>
        <p class="page-subtitle"><?= e($typeLabels[$req['type']] ?? $req['type']) ?></p>
    </div>
    <a href="/admin/privacidade/solicitacoes" class="btn btn-secondary">Voltar</a>
</div>

<div class="alert alert-info" style="margin-bottom:1.25rem;">
    <span><strong>Verifique a identidade do solicitante</strong> antes de fornecer dados ou executar exclusão. Não envie dados pessoais apenas porque alguém conhece o e-mail.</span>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Detalhes</div>
        <table class="data">
            <tbody>
                <tr><td>Tipo</td><td><?= e($typeLabels[$req['type']] ?? $req['type']) ?></td></tr>
                <tr><td>Status atual</td><td><?= e($statusLabels[$req['status']] ?? $req['status']) ?></td></tr>
                <tr><td>Titular (jogador)</td><td><?= !empty($req['player_id']) ? e($req['player_id']) : '—' ?></td></tr>
                <tr><td>E-mail</td><td><?= e(mask_email($req['email'] ?? '')) ?></td></tr>
                <tr><td>Criada</td><td><?= e(format_date($req['created_at'], 'd/m/Y H:i')) ?></td></tr>
                <?php if (!empty($req['completed_at'])): ?><tr><td>Concluída</td><td><?= e(format_date($req['completed_at'], 'd/m/Y H:i')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
        <?php if (!empty($req['message'])): ?>
            <div class="card-title mt-3">Mensagem do titular</div>
            <p class="muted"><?= e($req['message']) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">Tratamento</div>
        <form method="post" action="/admin/privacidade/solicitacoes/<?= (int) $req['id'] ?>/status">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= $req['status'] === $s ? 'selected' : '' ?>><?= e($statusLabels[$s] ?? $s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="admin_note">Nota interna</label>
                <textarea id="admin_note" name="admin_note" rows="4" placeholder="Registre o andamento (evite copiar dados pessoais em excesso)."><?= e($req['admin_note'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
        <p class="muted text-sm mt-2">
            A exclusão efetiva de conta pode depender da API do jogo e da preservação de registros exigidos por lei.
            Use o status para acompanhar o andamento.
        </p>
    </div>
</div>
