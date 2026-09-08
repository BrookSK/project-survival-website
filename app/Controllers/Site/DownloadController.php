<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameReleaseService;
use App\Services\GameApi\ReleaseInformation;
use App\Services\GameApi\UrlGuard;

/**
 * Download público do jogo.
 *
 * - `GET /download` — página com versão/tamanho/hash/requisitos (fonte oficial).
 * - `GET /download/project-survival` — ROTA PERMANENTE: redireciona ao
 *   instalador oficial atual. Não muda a cada versão (o site não hardcoda
 *   versão nem URL). A URL de destino vem SEMPRE da Game API/config e é
 *   validada (anti-SSRF/open-redirect) antes do redirect.
 *
 * O site nunca inventa versão/hash/URL: tudo vem de `GameReleaseService`.
 */
class DownloadController extends Controller
{
    private GameReleaseService $releases;

    public function __construct(?GameReleaseService $releases = null)
    {
        $this->releases = $releases ?? new GameReleaseService();
    }

    /**
     * Página de download.
     */
    public function index(Request $request): void
    {
        $channel = GameApiConfig::releaseChannel();
        $release = null;
        $stale = false;

        if (GameApiConfig::isEnabled() && !GameApiConfig::maintenance()) {
            $release = $this->releases->latest();
            $stale = $release !== null && $this->releases->isServingStale($channel);
        }

        $seo = \App\Services\SeoService::build([
            'title'       => 'Baixar Project Survival',
            'description' => 'Download oficial do Project Survival para Windows. Baixe o instalador, instale e comece a jogar.',
            'canonical'   => url('download'),
            'type'        => 'website',
        ]);

        // Structured data (SoftwareApplication) — só quando há release oficial.
        $jsonLd = null;
        if ($release instanceof ReleaseInformation && $release->isUsable()) {
            $jsonLd = [
                '@context'            => 'https://schema.org',
                '@type'               => 'SoftwareApplication',
                'name'                => $release->product,
                'operatingSystem'     => 'Windows',
                'applicationCategory' => 'GameApplication',
                'softwareVersion'     => $release->version,
                'downloadUrl'         => url('download/project-survival'),
            ];
        }

        $this->viewSite('site.download.index', [
            'title'       => 'Baixar Project Survival',
            'seo'         => $seo,
            'jsonLd'      => $jsonLd,
            'release'     => $release,
            'channel'     => $channel,
            'stale'       => $stale,
            'maintenance' => GameApiConfig::maintenance(),
            // A rota permanente é sempre interna (o botão nunca aponta direto ao arquivo).
            'downloadRoute' => url('download/project-survival'),
        ]);
    }

    /**
     * Rota permanente: resolve a release atual e redireciona ao instalador.
     *
     * A URL do instalador vem da Game API/config — NUNCA de parâmetro do
     * usuário. É validada por UrlGuard::isSafeDownloadUrl (SSRF/open-redirect,
     * HTTPS em produção, allowlist de hosts) antes do 302.
     */
    public function redirectToInstaller(Request $request): void
    {
        if (!GameApiConfig::isEnabled() || GameApiConfig::maintenance()) {
            $this->unavailable();
            return;
        }

        $release = $this->releases->latest();
        if (!($release instanceof ReleaseInformation) || !$release->isUsable()) {
            $this->unavailable();
            return;
        }

        $target = (string) $release->bestInstallerUrl();
        $allowed = $this->allowedHosts($release);

        if (!UrlGuard::isSafeDownloadUrl($target, $allowed)) {
            Logger::warning('download.blocked_url', ['host' => UrlGuard::host($target)]);
            $this->unavailable();
            return;
        }

        // Redireciona ao instalador oficial. 302 (temporário): a resolução é
        // dinâmica e a URL pode mudar entre versões.
        \App\Core\Response::redirect($target, 302);
    }

    /**
     * Hosts permitidos para o redirect: derivados da base URL da API e das
     * URLs de download configuradas, além do host da própria URL oficial da
     * release (que é fonte confiável). Uma allowlist opcional em settings
     * (`download_allowed_hosts`, CSV) restringe ainda mais.
     *
     * @return string[]
     */
    private function allowedHosts(ReleaseInformation $release): array
    {
        $hosts = [];

        // Host da base URL da API (mesma origem/infra do jogo).
        $apiHost = UrlGuard::host(GameApiConfig::baseUrl());
        if ($apiHost !== null) {
            $hosts[] = $apiHost;
        }

        // Hosts das URLs de instalador informadas pela própria API (confiáveis).
        foreach ([$release->installer['permanent_url'] ?? null, $release->installer['url'] ?? null, $release->downloadUrl] as $u) {
            if (is_string($u) && $u !== '') {
                $h = UrlGuard::host($u);
                if ($h !== null) {
                    $hosts[] = $h;
                }
            }
        }

        // Allowlist explícita opcional (settings), CSV de hosts.
        $csv = (string) setting('download_allowed_hosts', '');
        foreach (array_filter(array_map('trim', explode(',', $csv))) as $h) {
            $hosts[] = strtolower($h);
        }

        return array_values(array_unique($hosts));
    }

    /**
     * Resposta amigável quando não há release disponível (nunca erro fatal).
     */
    private function unavailable(): void
    {
        Session::flash('info', 'Informações de download temporariamente indisponíveis. Tente novamente em instantes.');
        $this->redirect('/download');
    }
}
