<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\EmailTemplate;
use App\Services\AuditService;
use App\Services\HtmlSanitizer;
use App\Validators\Validator;

/**
 * Gerenciamento de templates de e-mail.
 *
 * A chave (key) é imutável: identifica o template usado pelo sistema.
 * Novos templates só via seed/migration; aqui edita-se assunto/corpo/ativo.
 */
class EmailTemplateController extends Controller
{
    private EmailTemplate $templates;

    public function __construct()
    {
        $this->templates = new EmailTemplate();
    }

    public function index(Request $request): void
    {
        $this->authorize('email_templates.view');
        $this->viewAdmin('admin.email_templates.index', [
            'title'       => 'Templates de e-mail',
            'breadcrumbs' => [['label' => 'Templates de e-mail']],
            'items'       => $this->templates->allOrdered(),
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('email_templates.edit');
        $item = $this->templates->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.email_templates.form', [
            'title'       => 'Editar template',
            'breadcrumbs' => [['label' => 'Templates de e-mail', 'url' => '/admin/email-templates'], ['label' => 'Editar']],
            'template'    => $item,
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('email_templates.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->templates->find($id);
        if (!$item) {
            $this->abort(404);
        }

        $data = [
            'subject'   => trim((string) $request->post('subject', '')),
            'body'      => HtmlSanitizer::clean((string) $request->post('body', '')),
            'is_active' => $request->post('is_active') ? 1 : 0,
        ];

        $v = new Validator($data, [
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ], ['subject' => 'assunto', 'body' => 'conteúdo']);

        if ($v->fails()) {
            $this->redirectWithErrors($v->errors(), $data, "/admin/email-templates/{$id}/editar");
            return;
        }

        $this->templates->update($id, $data);
        AuditService::log('update', 'email_templates', (string) $id, "Editou o template: {$item['key']}");
        Session::flash('success', 'Template atualizado.');
        $this->redirect('/admin/email-templates');
    }
}
