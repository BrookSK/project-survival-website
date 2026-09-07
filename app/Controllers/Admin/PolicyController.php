<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\PrivacyPolicy;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\HtmlSanitizer;

/**
 * Gestão dos documentos legais versionados (Política de Privacidade, Termos de
 * Uso, Termos de Compra, Política de Reembolso, Política de Cookies).
 *
 * Uma versão publicada é imutável: alterações criam uma nova versão. Publicar
 * arquiva a versão publicada anterior e registra responsável/data.
 */
class PolicyController extends Controller
{
    private PrivacyPolicy $policies;

    public function __construct()
    {
        $this->policies = new PrivacyPolicy();
    }

    public function index(Request $request): void
    {
        $this->authorize('privacy.view');

        $docs = $this->policies->allOrdered();
        $rows = [];
        foreach ($docs as $doc) {
            $published = $this->policies->publishedVersion((int) $doc['id']);
            $rows[] = [
                'policy'    => $doc,
                'published' => $published,
            ];
        }

        $this->viewAdmin('admin.privacy.policies.index', [
            'title'       => 'Documentos legais',
            'breadcrumbs' => [['label' => 'Privacidade'], ['label' => 'Documentos']],
            'rows'        => $rows,
        ]);
    }

    /**
     * Histórico de versões de um documento.
     */
    public function history(Request $request, array $params): void
    {
        $this->authorize('privacy.view');

        $policy = $this->policies->find((int) ($params['id'] ?? 0));
        if (!$policy) {
            $this->abort(404);
        }

        $this->viewAdmin('admin.privacy.policies.history', [
            'title'       => 'Histórico — ' . $policy['title'],
            'breadcrumbs' => [['label' => 'Privacidade'], ['label' => 'Documentos', 'url' => '/admin/privacidade/documentos'], ['label' => $policy['title']]],
            'policy'      => $policy,
            'versions'    => $this->policies->versions((int) $policy['id']),
        ]);
    }

    /**
     * Formulário para criar uma nova versão (rascunho) do documento, pré-carregando
     * o conteúdo da última versão como ponto de partida.
     */
    public function editForm(Request $request, array $params): void
    {
        $this->authorize('privacy.manage');

        $policy = $this->policies->find((int) ($params['id'] ?? 0));
        if (!$policy) {
            $this->abort(404);
        }

        // Edita um rascunho existente, se houver; senão prepara uma nova versão.
        $versions = $this->policies->versions((int) $policy['id']);
        $draft = null;
        $lastContent = '';
        $lastVersion = '';
        foreach ($versions as $v) {
            if ($v['status'] === 'draft' && $draft === null) {
                $draft = $v;
            }
            if ($lastContent === '' && !empty($v['content'])) {
                $lastContent = $v['content'];
                $lastVersion = $v['version'];
            }
        }

        $this->viewAdmin('admin.privacy.policies.edit', [
            'title'       => 'Editar — ' . $policy['title'],
            'breadcrumbs' => [['label' => 'Privacidade'], ['label' => 'Documentos', 'url' => '/admin/privacidade/documentos'], ['label' => $policy['title']]],
            'policy'      => $policy,
            'draft'       => $draft,
            'suggestedContent' => $draft['content'] ?? $lastContent,
            'lastVersion' => $lastVersion,
            'errors'      => errors(),
        ]);
    }

    /**
     * Salva um rascunho: cria nova versão ou atualiza o rascunho existente.
     */
    public function save(Request $request, array $params): void
    {
        $this->authorize('privacy.manage');
        $this->verifyCsrf($request);

        $policy = $this->policies->find((int) ($params['id'] ?? 0));
        if (!$policy) {
            $this->abort(404);
        }

        $version = trim((string) $request->post('version', ''));
        $content = HtmlSanitizer::clean((string) $request->post('content', ''));
        $effectiveAt = trim((string) $request->post('effective_at', ''));
        $effectiveAt = $effectiveAt !== '' ? date('Y-m-d H:i:s', strtotime($effectiveAt)) : null;
        $draftId = (int) $request->post('draft_id', 0);

        if ($version === '') {
            $this->redirectWithErrors(['version' => 'Informe a versão (ex.: 1.0).'], [], '/admin/privacidade/documentos/' . $policy['id'] . '/editar');
            return;
        }

        if ($draftId > 0) {
            $this->policies->updateDraft($draftId, $content, $effectiveAt);
            AuditService::log('update', 'privacy_policy', (string) $policy['id'], "Atualizou rascunho de {$policy['title']}");
        } else {
            $this->policies->createVersion((int) $policy['id'], $version, $content, $effectiveAt);
            AuditService::log('create', 'privacy_policy', (string) $policy['id'], "Criou versão {$version} de {$policy['title']}");
        }

        Session::flash('success', 'Rascunho salvo. Revise e publique quando estiver pronto.');
        $this->redirect('/admin/privacidade/documentos/' . $policy['id'] . '/historico');
    }

    /**
     * Publica uma versão (rascunho -> publicado).
     */
    public function publish(Request $request, array $params): void
    {
        $this->authorize('privacy.manage');
        $this->verifyCsrf($request);

        $versionId = (int) ($params['version'] ?? 0);
        $version = $this->policies->findVersion($versionId);
        if (!$version) {
            $this->abort(404);
        }

        $userId = (int) (AuthService::user()['id'] ?? 0);
        $ok = $this->policies->publishVersion($versionId, $userId);

        if ($ok) {
            AuditService::log('publish', 'privacy_policy', (string) $version['policy_id'], "Publicou versão {$version['version']}");
            Session::flash('success', 'Versão publicada e vigente.');
        } else {
            Session::flash('error', 'Não foi possível publicar a versão.');
        }

        $this->redirect('/admin/privacidade/documentos/' . $version['policy_id'] . '/historico');
    }
}
