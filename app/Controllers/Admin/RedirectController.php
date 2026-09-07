<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Redirect;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * Gerenciamento de redirecionamentos (SEO / mudanças de URL).
 */
class RedirectController extends Controller
{
    private Redirect $redirects;

    public function __construct()
    {
        $this->redirects = new Redirect();
    }

    public function index(Request $request): void
    {
        $this->authorize('redirects.view');
        $this->viewAdmin('admin.redirects.index', [
            'title'       => 'Redirecionamentos',
            'breadcrumbs' => [['label' => 'Redirects']],
            'items'       => $this->redirects->allOrdered(),
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('redirects.edit');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/redirects');
            return;
        }

        $id = $this->redirects->create($data);
        AuditService::log('create', 'redirects', (string) $id, "Criou redirect: {$data['from_path']}");
        Session::flash('success', 'Redirecionamento criado.');
        $this->redirect('/admin/redirects');
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('redirects.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->redirects->find($id)) {
            $this->abort(404);
        }
        $data = $this->collect($request);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/redirects');
            return;
        }

        $this->redirects->update($id, $data);
        AuditService::log('update', 'redirects', (string) $id, "Editou redirect: {$data['from_path']}");
        Session::flash('success', 'Redirecionamento atualizado.');
        $this->redirect('/admin/redirects');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('redirects.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->redirects->find($id)) {
            $this->abort(404);
        }
        $this->redirects->delete($id);
        AuditService::log('delete', 'redirects', (string) $id, 'Removeu redirect');
        Session::flash('success', 'Redirecionamento removido.');
        $this->redirect('/admin/redirects');
    }

    private function collect(Request $request): array
    {
        $from = '/' . ltrim(trim((string) $request->post('from_path', '')), '/');
        $code = (int) $request->post('status_code', 301);
        return [
            'from_path'   => $from,
            'to_url'      => trim((string) $request->post('to_url', '')),
            'status_code' => in_array($code, [301, 302], true) ? $code : 301,
            'is_active'   => $request->post('is_active') ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'from_path' => 'required|string|max:255',
            'to_url'    => 'required|string|max:255',
        ], ['from_path' => 'caminho de origem', 'to_url' => 'destino']);
        $errors = $v->errors();
        if ($data['from_path'] === $data['to_url']) {
            $errors['to_url'] = 'O destino não pode ser igual à origem.';
        }
        if (!isset($errors['from_path']) && $this->redirects->fromExists($data['from_path'], $ignoreId)) {
            $errors['from_path'] = 'Já existe um redirect para este caminho.';
        }
        return $errors;
    }
}
