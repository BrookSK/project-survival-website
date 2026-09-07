<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * CRUD de perguntas frequentes.
 */
class FaqController extends Controller
{
    private Faq $faqs;
    private FaqCategory $categories;

    public function __construct()
    {
        $this->faqs = new Faq();
        $this->categories = new FaqCategory();
    }

    public function index(Request $request): void
    {
        $this->authorize('faq.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) setting('items_per_page', 15);
        $search = trim((string) $request->query('q', ''));

        $result = $this->faqs->paginate($page, $perPage, $search);

        $this->viewAdmin('admin.faq.index', [
            'title'       => 'FAQ',
            'breadcrumbs' => [['label' => 'FAQ']],
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'search'      => $search,
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('faq.create');
        $this->viewAdmin('admin.faq.form', [
            'title'       => 'Nova pergunta',
            'breadcrumbs' => [['label' => 'FAQ', 'url' => '/admin/faq'], ['label' => 'Nova']],
            'faq_item'    => null,
            'categories'  => $this->categories->allOrdered(),
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('faq.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/faq/criar');
            return;
        }

        $id = $this->faqs->create($data);
        AuditService::log('create', 'faq', (string) $id, 'Criou uma pergunta frequente');
        Session::flash('success', 'Pergunta criada com sucesso.');
        $this->redirect('/admin/faq');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('faq.edit');
        $item = $this->faqs->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.faq.form', [
            'title'       => 'Editar pergunta',
            'breadcrumbs' => [['label' => 'FAQ', 'url' => '/admin/faq'], ['label' => 'Editar']],
            'faq_item'    => $item,
            'categories'  => $this->categories->allOrdered(),
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('faq.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->faqs->find($id)) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/faq/{$id}/editar");
            return;
        }

        $this->faqs->update($id, $data);
        AuditService::log('update', 'faq', (string) $id, 'Editou uma pergunta frequente');
        Session::flash('success', 'Pergunta atualizada.');
        $this->redirect('/admin/faq');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('faq.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->faqs->find($id)) {
            $this->abort(404);
        }
        $this->faqs->delete($id);
        AuditService::log('delete', 'faq', (string) $id, 'Excluiu uma pergunta frequente');
        Session::flash('success', 'Pergunta excluída.');
        $this->redirect('/admin/faq');
    }

    private function collect(Request $request): array
    {
        $catId = (int) $request->post('category_id', 0);
        return [
            'category_id' => $catId > 0 ? $catId : null,
            'question'    => trim((string) $request->post('question', '')),
            'answer'      => \App\Services\HtmlSanitizer::clean((string) $request->post('answer', '')),
            'is_active'   => $request->post('is_active') ? 1 : 0,
            'sort_order'  => (int) $request->post('sort_order', 0),
        ];
    }

    private function validate(array $data): array
    {
        $v = new Validator($data, [
            'question' => 'required|string|min:3|max:255',
            'answer'   => 'required|string',
        ], ['question' => 'pergunta', 'answer' => 'resposta']);
        return $v->errors();
    }
}
