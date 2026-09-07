<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Models\DataExport;
use App\Models\PrivacyConsent;
use App\Models\PrivacyRequest;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\GameAuthService;
use App\Services\GameApi\PlayerSession;

/**
 * Central de Privacidade do jogador (protegida por PlayerAuthMiddleware).
 *
 * Permite ao titular ver seus dados/integrações, gerenciar consentimentos
 * opcionais (ex.: marketing), exportar seus dados (JSON, sem segredos) e
 * solicitar exclusão da conta (fluxo com verificação — não apaga na hora).
 *
 * A conta do jogador é da Game API; aqui tratamos apenas os dados que o site
 * detém (consentimentos, solicitações) e apresentamos o que a API retorna.
 */
class AccountPrivacyController extends Controller
{
    private PrivacyConsent $consents;
    private PrivacyRequest $requests;
    private GameAuthService $auth;

    public function __construct()
    {
        $this->consents = new PrivacyConsent();
        $this->requests = new PrivacyRequest();
        $this->auth = new GameAuthService();
    }

    /**
     * Visão geral: dados do jogador (da API), integrações e consentimentos.
     */
    public function index(Request $request): void
    {
        $playerId = (string) (PlayerSession::userId() ?? '');
        $me = PlayerSession::user();

        // Tenta atualizar os dados a partir da API (best-effort).
        try {
            $fresh = PlayerSession::withAuth(fn ($t) => $this->auth->me($t));
            if ($fresh !== []) {
                PlayerSession::setUser($fresh);
                $me = $fresh;
            }
        } catch (GameApiException $e) {
            // Mantém os dados de sessão em caso de indisponibilidade.
        }

        $consentRows = $playerId !== '' ? $this->consents->forPlayer($playerId) : [];
        $marketing = $this->consents->has($playerId, 'marketing');

        $latestExport = $playerId !== '' ? (new DataExport())->latestForPlayer($playerId) : null;

        $this->viewSite('site.account.privacy.index', [
            'title'        => 'Privacidade',
            'me'           => $me,
            'consents'     => $consentRows,
            'marketing'    => $marketing,
            'latestExport' => $latestExport,
        ]);
    }

    /**
     * Atualiza consentimentos opcionais (marketing). Consentimentos
     * essenciais/obrigatórios não são geridos aqui.
     */
    public function updateConsents(Request $request): void
    {
        $this->verifyCsrf($request);

        $playerId = (string) (PlayerSession::userId() ?? '');
        if ($playerId === '') {
            $this->redirect('/conta/privacidade');
            return;
        }

        $marketing = (bool) $request->post('marketing');
        $this->consents->record(
            $playerId,
            'marketing',
            $marketing,
            null,
            'privacy_center',
            $request->ip(),
            $request->userAgent()
        );

        Session::flash('success', $marketing
            ? 'Você optou por receber comunicações de marketing.'
            : 'Você não receberá mais comunicações de marketing.');
        $this->redirect('/conta/privacidade');
    }

    /**
     * Gera uma exportação dos dados que o site detém sobre o titular.
     * NUNCA inclui tokens, hashes ou segredos.
     */
    public function export(Request $request): void
    {
        $this->verifyCsrf($request);

        $playerId = (string) (PlayerSession::userId() ?? '');
        if ($playerId === '') {
            $this->redirect('/conta/privacidade');
            return;
        }

        // Monta o pacote apenas com dados apresentáveis (sem segredos).
        $me = PlayerSession::user();
        $account = [
            'player_id' => $playerId,
            'username'  => $me['username'] ?? null,
            'email'     => $me['email'] ?? null,
            'name'      => $me['name'] ?? null,
        ];

        $consentRows = array_map(static function (array $c): array {
            return [
                'consent_type' => $c['consent_type'],
                'version'      => $c['version'],
                'granted'      => (bool) $c['granted'],
                'granted_at'   => $c['granted_at'],
                'revoked_at'   => $c['revoked_at'],
            ];
        }, $this->consents->forPlayer($playerId));

        $requestRows = $this->requestsForPlayer($playerId);

        $payload = [
            'generated_at' => date('c'),
            'notice'       => 'Exportação de dados que o website detém. Dados do jogo (inventário, '
                            . 'entitlements) são geridos pela API do jogo e disponibilizados sob solicitação.',
            'account'      => $account,
            'consents'     => $consentRows,
            'privacy_requests' => $requestRows,
        ];

        $dir = STORAGE_PATH . '/exports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = 'export_' . $playerId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.json';
        $path = $dir . '/' . $filename;
        $written = @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($written === false) {
            Logger::warning('privacy.export.write_failed', ['player' => $playerId]);
            Session::flash('error', 'Não foi possível gerar a exportação agora. Tente novamente.');
            $this->redirect('/conta/privacidade');
            return;
        }

        $ttlDays = max(1, (int) setting('retention_export_days', 7));
        $export = (new DataExport())->createReady($playerId, $path, $ttlDays);

        Logger::info('privacy.export.created', ['player' => $playerId]);
        Session::flash('success', 'Exportação gerada. O download expira em ' . $ttlDays . ' dia(s).');
        // Guarda o token na sessão do titular para o link de download desta vez.
        Session::set('__last_export_token', $export['token']);
        $this->redirect('/conta/privacidade');
    }

    /**
     * Baixa a exportação. Exige jogador autenticado + token válido e não
     * expirado, pertencente ao próprio jogador. O arquivo vive fora de /public.
     */
    public function download(Request $request): void
    {
        $playerId = (string) (PlayerSession::userId() ?? '');
        $token = (string) $request->query('token', '');
        if ($playerId === '' || $token === '') {
            $this->abort(404);
        }

        $export = (new DataExport())->findValidForPlayer($playerId, $token);
        if (!$export || empty($export['file_path']) || !is_file($export['file_path'])) {
            $this->abort(404);
        }

        (new DataExport())->markDownloaded((int) $export['id']);

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="meus-dados.json"');
        header('X-Content-Type-Options: nosniff');
        readfile($export['file_path']);
        exit;
    }

    /**
     * Abre uma solicitação de exclusão de conta. Requer confirmação forte.
     * NÃO apaga imediatamente: cria um pedido a ser tratado (com verificação),
     * pois há dependências (Game API, obrigações legais/operacionais).
     */
    public function requestDeletion(Request $request): void
    {
        $this->verifyCsrf($request);

        $playerId = (string) (PlayerSession::userId() ?? '');
        $confirm = strtoupper(trim((string) $request->post('confirm', '')));

        if ($confirm !== 'EXCLUIR') {
            Session::flash('error', 'Digite EXCLUIR para confirmar a solicitação.');
            $this->redirect('/conta/privacidade');
            return;
        }

        $me = PlayerSession::user();
        $this->requests->open(
            'deletion',
            $playerId !== '' ? $playerId : null,
            $me['email'] ?? null,
            trim((string) $request->post('message', '')) ?: 'Solicitação de exclusão de conta.',
            $request->ip()
        );

        Logger::info('privacy.request.deletion', ['player' => $playerId]);
        Session::flash('success', 'Solicitação de exclusão registrada. Nossa equipe fará a verificação e o retorno pelo e-mail da conta.');
        $this->redirect('/conta/privacidade');
    }

    /**
     * Solicitações de privacidade do próprio jogador (para exibição).
     */
    private function requestsForPlayer(string $playerId): array
    {
        try {
            return $this->requests->forPlayer($playerId);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
