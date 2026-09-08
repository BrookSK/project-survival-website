<?php
/**
 * @var bool $enabled
 * @var bool $maintenance
 * @var string $baseUrl
 * @var string $clientId
 * @var int $timeout
 * @var bool $cacheEnabled
 * @var string $apiVersion
 * @var string $releaseChannel
 * @var \App\Services\GameApi\ReleaseInformation|null $release
 * @var array $websiteUrls
 * @var array $health
 */
$canManage = has_permission('system.manage');
$statusOnline = !empty($health['online']);
$maintenance = $maintenance ?? false;
$release = $release ?? null;
$websiteUrls = $websiteUrls ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Integração com a API do Jogo</h1>
        <p class="page-subtitle">Conexão do site com a API oficial do Project Survival.</p>
    </div>
    <a href="/admin/configuracoes?grupo=game_api_cache" class="btn btn-secondary">Configurar cache</a>
</div>

<div class="grid-2">
    <!-- Status -->
    <div class="card">
        <div class="card-title">Status</div>
        <?php if (!$enabled): ?>
            <div class="alert alert-info"><span>A integração está <strong>desativada</strong>. Ative-a abaixo para conectar à API.</span></div>
        <?php endif; ?>
        <table class="data">
            <tbody>
                <tr>
                    <td>API</td>
                    <td>
                        <?php if ($statusOnline): ?>
                            <span class="badge badge-success">Online</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Offline</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($maintenance): ?>
                    <tr><td>Manutenção</td><td><span class="badge badge-warning">Ativa</span></td></tr>
                <?php endif; ?>
                <tr><td>Banco da API</td><td><?= e($health['database'] ?? '—') ?></td></tr>
                <tr><td>Versão (reportada)</td><td><?= e($health['api_version'] ?? '—') ?></td></tr>
                <tr><td>Versão (configurada)</td><td><?= e($apiVersion) ?></td></tr>
                <tr><td>Latência</td><td><?= (int) ($health['latency_ms'] ?? 0) ?> ms</td></tr>
                <tr><td>HTTP</td><td><?= (int) ($health['http_status'] ?? 0) ?></td></tr>
                <?php if (!empty($health['error'])): ?>
                    <tr><td>Último erro</td><td><span class="text-danger"><?= e($health['error']) ?></span></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <form method="post" action="/admin/integracoes/testar" class="mt-2">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Testar conexão</button>
        </form>
    </div>

    <!-- Conexão -->
    <div class="card">
        <div class="card-title">Conexão</div>
        <?php if ($canManage): ?>
            <form method="post" action="/admin/integracoes/conexao">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="game_api_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                        Ativar integração
                    </label>
                </div>
                <div class="form-group">
                    <label for="base_url">URL base da API</label>
                    <input type="text" id="base_url" name="game_api_base_url" value="<?= e($baseUrl) ?>" placeholder="https://api.exemplo.com/api/v1">
                    <div class="label-hint">Inclua o caminho <code>/api/v1</code>. Em produção, use HTTPS.</div>
                </div>
                <div class="form-group">
                    <label for="client_id">Client ID</label>
                    <input type="text" id="client_id" name="game_api_client_id" value="<?= e($clientId) ?>" placeholder="(opcional)">
                    <div class="label-hint">Identificador público do cliente. Não é segredo.</div>
                </div>
                <div class="form-group">
                    <label for="timeout">Timeout (segundos)</label>
                    <input type="number" id="timeout" name="game_api_timeout" value="<?= (int) $timeout ?>" min="1" max="30">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar conexão</button>
                </div>
            </form>
        <?php else: ?>
            <table class="data">
                <tbody>
                    <tr><td>Ativa</td><td><?= $enabled ? 'Sim' : 'Não' ?></td></tr>
                    <tr><td>URL base</td><td><?= e($baseUrl) ?></td></tr>
                    <tr><td>Client ID</td><td><?= $clientId !== '' ? e($clientId) : '—' ?></td></tr>
                    <tr><td>Timeout</td><td><?= (int) $timeout ?> s</td></tr>
                </tbody>
            </table>
            <p class="muted text-sm">Você não tem permissão para editar a conexão.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Distribuição / Release -->
<div class="card mt-3">
    <div class="card-title">Distribuição / Download</div>
    <table class="data">
        <tbody>
            <tr><td>Canal de release</td><td><span class="badge badge-muted"><?= e($releaseChannel) ?></span></td></tr>
            <?php if ($release !== null): ?>
                <tr><td>Versão atual</td><td>v<?= e($release->version) ?></td></tr>
                <tr><td>Plataforma</td><td><?= e($release->platform) ?></td></tr>
                <tr><td>Tamanho</td><td><?= e($release->sizeLabel() ?? '—') ?></td></tr>
                <tr><td>SHA-256</td><td><code style="font-size:.75rem;word-break:break-all;"><?= e($release->sha256() ?? '—') ?></code></td></tr>
                <tr><td>URL do instalador</td><td><code style="font-size:.75rem;word-break:break-all;"><?= e($release->bestInstallerUrl() ?? '—') ?></code></td></tr>
            <?php else: ?>
                <tr><td colspan="2"><span class="muted">Nenhuma release disponível (API indisponível, em manutenção ou sem publicação no canal).</span></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p class="muted text-sm mt-2">
        Fonte oficial: <code>GET /public/download/<?= e($releaseChannel) ?></code>. O site não publica releases.
        Configure o canal em <a href="/admin/configuracoes?grupo=releases">Configurações → Releases / Download</a>.
    </p>
</div>

<!-- URLs do site -->
<div class="card mt-3">
    <div class="card-title">URLs configuradas</div>
    <table class="data">
        <tbody>
            <?php foreach ($websiteUrls as $name => $u): ?>
                <tr><td><?= e(ucfirst($name)) ?></td><td><code style="font-size:.8rem;word-break:break-all;"><?= e($u) ?></code></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="muted text-sm mt-2">
        Combine os domínios com a equipe do jogo. Ajuste em
        <a href="/admin/configuracoes?grupo=website_urls">Configurações → URLs do site</a>.
    </p>
</div>

<!-- Cache -->
<div class="card mt-3">
    <div class="card-title">Cache da API</div>
    <p class="muted mb-2">
        Cache das leituras públicas (notícias, eventos, loja, config):
        <strong><?= $cacheEnabled ? 'ativado' : 'desativado' ?></strong>.
        Ajuste os tempos em <a href="/admin/configuracoes?grupo=game_api_cache">Configurações → Cache da API</a>.
    </p>
    <?php if ($canManage): ?>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <?php foreach (['all' => 'Tudo', 'news' => 'Notícias', 'events' => 'Eventos', 'store' => 'Loja', 'config' => 'Config', 'release' => 'Release', 'status' => 'Status'] as $scope => $label): ?>
                <form method="post" action="/admin/integracoes/cache/limpar">
                    <?= csrf_field() ?>
                    <input type="hidden" name="scope" value="<?= e($scope) ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">Limpar: <?= e($label) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
