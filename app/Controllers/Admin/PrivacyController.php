<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\DataExport;
use App\Models\PrivacyConsent;
use App\Models\PrivacyRequest;
use App\Services\AuditService;
use App\Services\AuthService;

/**
 * Painel administrativo de privacidade: solicitações de titulares, visão de
 * consentimentos e exportações.
 *
 * Operações registram auditoria da AÇÃO (não copiam dados pessoais para o log).
 * A verificação de identidade do solicitante é responsabilidade do operador
 * antes de concluir acesso/exclusão (lembrete exibido na tela).
 */
class PrivacyController extends Controller
{
    private PrivacyRequest $requests;

    public function __construct()
    {
        $this->requests = new PrivacyRequest();
    }

    /**
     * Visão geral de privacidade (dashboard leve).
     */
    public function dashboard(Request $request): void
    {
        $this->authorize('privacy.view');

        $statusCounts = [];
        foreach (PrivacyRequest::STATUSES as $s) {
            $statusCounts[$s] = $this->requests->countByStatus($s);
        }

        $this->viewAdmin('admin.privacy.dashboard', [
            'title'        => 'Privacidade',
            'breadcrumbs'  => [['label' => 'Privacidade']],
            'statusCounts' => $statusCounts,
            'consents'     => (new PrivacyConsent())->summaryByType(),
            'exports'      => (new DataExport())->recent(20),
        ]);
    }

    /**
     * Lista de solicitações de titulares, com filtros.
     */
    public function requests(Request $request): void
    {
        $this->authorize('privacy.requests');

        $page = (int) $request->query('page', 1);
        $filters = [
            'status' => (string) $request->query('status', ''),
            'type'   => (string) $request->query('type', ''),
        ];

        $result = $this->requests->paginate($page, 20, $filters);

        $this->viewAdmin('admin.privacy.requests.index', [
            'title'       => 'Solicitações de privacidade',
            'breadcrumbs' => [['label' => 'Privacidade', 'url' => '/admin/privacidade'], ['label' => 'Solicitações']],
            'result'      => $result,
            'filters'     => $filters,
            'statuses'    => PrivacyRequest::STATUSES,
            'types'       => PrivacyRequest::TYPES,
        ]);
    }

    /**
     * Detalhe de uma solicitação.
     */
    public function show(Request $request, array $params): void
    {
        $this->authorize('privacy.requests');

        $req = $this->requests->find((int) ($params['id'] ?? 0));
        if (!$req) {
            $this->abort(404);
        }

        $this->viewAdmin('admin.privacy.requests.show', [
            'title'       => 'Solicitação',
            'breadcrumbs' => [['label' => 'Privacidade', 'url' => '/admin/privacidade'], ['label' => 'Solicitações', 'url' => '/admin/privacidade/solicitacoes'], ['label' => $req['public_id']]],
            'req'         => $req,
            'statuses'    => PrivacyRequest::STATUSES,
        ]);
    }

    /**
     * Atualiza o status/nota de uma solicitação. Registra auditoria (ação).
     */
    public function updateStatus(Request $request, array $params): void
    {
        $this->authorize('privacy.requests');
        $this->verifyCsrf($request);

        $req = $this->requests->find((int) ($params['id'] ?? 0));
        if (!$req) {
            $this->abort(404);
        }

        $status = (string) $request->post('status', '');
        $note = trim((string) $request->post('admin_note', '')) ?: null;

        if (!in_array($status, PrivacyRequest::STATUSES, true)) {
            Session::flash('error', 'Status inválido.');
            $this->redirect('/admin/privacidade/solicitacoes/' . $req['id']);
            return;
        }

        $userId = (int) (AuthService::user()['id'] ?? 0);
        $this->requests->setStatus((int) $req['id'], $status, $userId, $note);

        // Auditoria registra a AÇÃO, não os dados pessoais do titular.
        AuditService::log('update', 'privacy_request', $req['public_id'], "Alterou status para {$status}");

        // Ao CONCLUIR uma exclusão, purga os dados que o SITE detém sobre o
        // titular (consentimentos). Exige a permissão de maior risco
        // (privacy.delete) — concessão explícita. A conta do jogo é encerrada
        // pelo processo da Game API; registros exigidos por lei são preservados.
        if ($status === 'completed' && $req['type'] === 'deletion' && !empty($req['player_id'])
            && AuthService::can('privacy.delete')) {
            $removed = (new PrivacyConsent())->purgePlayer((string) $req['player_id']);
            AuditService::log('delete', 'privacy_request', $req['public_id'], "Purgou consentimentos do titular ({$removed} registro(s)).");
        }

        Session::flash('success', 'Solicitação atualizada.');
        $this->redirect('/admin/privacidade/solicitacoes/' . $req['id']);
    }
}
