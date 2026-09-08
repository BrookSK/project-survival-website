<?php
/**
 * @var array $result
 */
use App\Services\Commerce\OrderPresenter;
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Cupons</h1>
        <p class="page-subtitle">Descontos validados no backend (o navegador nunca define o valor).</p>
    </div>
</div>

<div class="card">
    <h2 class="mb-2">Novo cupom</h2>
    <form method="post" action="/admin/loja/cupons" class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.8rem;">
        <?= csrf_field() ?>
        <div class="form-group"><label>Código</label><input type="text" name="code" maxlength="40" required></div>
        <div class="form-group"><label>Descrição</label><input type="text" name="description" maxlength="190"></div>
        <div class="form-group"><label>Tipo</label>
            <select name="type"><option value="percent">Percentual (%)</option><option value="fixed">Valor fixo</option></select>
        </div>
        <div class="form-group"><label>Percentual (0-100)</label><input type="number" name="percent_off" step="0.01" min="0" max="100"></div>
        <div class="form-group"><label>Valor fixo</label><input type="number" name="amount_off" step="0.01" min="0"></div>
        <div class="form-group"><label>Moeda</label><input type="text" name="currency" value="BRL" maxlength="3"></div>
        <div class="form-group"><label>Pedido mínimo</label><input type="number" name="min_total" step="0.01" min="0" value="0"></div>
        <div class="form-group"><label>Usos máximos (0=ilimitado)</label><input type="number" name="max_redemptions" min="0" value="0"></div>
        <div class="form-group"><label>Limite por jogador (0=ilimitado)</label><input type="number" name="per_player_limit" min="0" value="0"></div>
        <div class="form-group"><label>Início</label><input type="datetime-local" name="starts_at"></div>
        <div class="form-group"><label>Fim</label><input type="datetime-local" name="ends_at"></div>
        <div class="form-group"><label><input type="checkbox" name="active" value="1" checked> Ativo</label></div>
        <div class="form-group" style="align-self:end;"><button type="submit" class="btn btn-primary btn-sm">Criar cupom</button></div>
    </form>
</div>

<div class="card">
    <?php if (empty($result['data'])): ?>
        <div class="empty-state"><p>Nenhum cupom.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Código</th><th>Tipo</th><th>Desconto</th><th>Usos</th><th>Ativo</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($result['data'] as $c): ?>
                        <tr>
                            <td><code><?= e($c['code']) ?></code></td>
                            <td><?= $c['type'] === 'percent' ? 'Percentual' : 'Fixo' ?></td>
                            <td><?= $c['type'] === 'percent' ? e(rtrim(rtrim((string) $c['percent_off'], '0'), '.')) . '%' : e(OrderPresenter::money((int) $c['amount_off_cents'], $c['currency'])) ?></td>
                            <td><?= (int) $c['redeemed_count'] ?><?= $c['max_redemptions'] !== null ? '/' . (int) $c['max_redemptions'] : '' ?></td>
                            <td><span class="badge <?= ((int) $c['active'] === 1) ? 'badge-success' : 'badge-muted' ?>"><?= ((int) $c['active'] === 1) ? 'Sim' : 'Não' ?></span></td>
                            <td class="actions">
                                <form method="post" action="/admin/loja/cupons/<?= (int) $c['id'] ?>/excluir">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remover o cupom <?= e($c['code']) ?>?" data-form>Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= partial('admin.partials.pagination', ['page' => $result['page'], 'total' => $result['total'], 'perPage' => $result['per_page'], 'baseUrl' => '/admin/loja/cupons', 'query' => []]) ?>
    <?php endif; ?>
</div>
