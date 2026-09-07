<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Page;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * CRUD de páginas de conteúdo.
 */
class PageController extends Controller
{
    private Page $pages;

    public function __construct()
    {
        $this->pages = new Page();
    }

    public function index(Request $request): void
    {
        $this->authorize('pages.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) setting('items_per_page', 15);
        $search = trim((string) $request->query('q', ''));

        $result = $this->pages->paginate($page, $perPage, $search);

        $this->viewAdmin('admin.pages.index', [
            'title'       => 'Páginas',
            'breadcrumbs' => [['label' => 'Páginas']],
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'search'      => $search,
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('pages.create');
        $this->viewAdmin('admin.pages.form', [
            'title'       => 'Nova página',
            'breadcrumbs' => [['label' => 'Páginas', 'url' => '/admin/paginas'], ['label' => 'Nova']],
            'page_item'   => null,
            'meta'        => null,
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('pages.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/paginas/criar');
            return;
        }

        $id = $this->pages->create([
            'title'        => $data['title'],
            'slug'         => $data['slug'],
            'content'      => $data['content'],
            'status'       => $data['status'],
            'template'     => 'default',
            'author_id'    => auth_user()['id'],
            'published_at' => $data['status'] === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        $this->pages->saveMeta($id, $data['meta']);
        AuditService::log('create', 'pages', (string) $id, "Criou a página: {$data['title']}");

        Session::flash('success', 'Página criada com sucesso.');
        $this->redirect('/admin/paginas');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('pages.edit');
        $item = $this->pages->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }

        $this->viewAdmin('admin.pages.form', [
            'title'       => 'Editar página',
            'breadcrumbs' => [['label' => 'Páginas', 'url' => '/admin/paginas'], ['label' => 'Editar']],
            'page_item'   => $item,
            'meta'        => $this->pages->meta((int) $item['id']),
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('pages.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->pages->find($id);
        if (!$item) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/paginas/{$id}/editar");
            return;
        }

        $update = [
            'title'   => $data['title'],
            'slug'    => $data['slug'],
            'content' => $data['content'],
            'status'  => $data['status'],
        ];
        // Define published_at ao publicar pela primeira vez
        if ($data['status'] === 'published' && empty($item['published_at'])) {
            $update['published_at'] = date('Y-m-d H:i:s');
        }

        $this->pages->update($id, $update);
        $this->pages->saveMeta($id, $data['meta']);
        AuditService::log('update', 'pages', (string) $id, "Editou a página: {$data['title']}");

        Session::flash('success', 'Página atualizada com sucesso.');
        $this->redirect('/admin/paginas');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('pages.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->pages->find($id);
        if (!$item) {
            $this->abort(404);
        }

        if ((int) $item['is_system'] === 1) {
            Session::flash('error', 'Esta página é essencial ao sistema e não pode ser excluída.');
            $this->redirect('/admin/paginas');
            return;
        }

        $this->pages->delete($id);
        AuditService::log('delete', 'pages', (string) $id, "Excluiu a página: {$item['title']}");

        Session::flash('success', 'Página excluída com sucesso.');
        $this->redirect('/admin/paginas');
    }

    /**
     * Coleta e normaliza os dados do formulário.
     */
    private function collect(Request $request): array
    {
        $title = trim((string) $request->post('title', ''));
        $slug = trim((string) $request->post('slug', ''));
        $slug = $slug !== '' ? str_slug($slug) : str_slug($title);

        return [
            'title'   => $title,
            'slug'    => $slug,
            'content' => \App\Services\HtmlSanitizer::clean((string) $request->post('content', '')),
            'status'  => in_array($request->post('status'), ['draft', 'published', 'archived'], true) ? $request->post('status') : 'draft',
            'meta'    => [
                'seo_title'       => trim((string) $request->post('seo_title', '')),
                'seo_description' => trim((string) $request->post('seo_description', '')),
                'seo_keywords'    => trim((string) $request->post('seo_keywords', '')),
                'og_image'        => trim((string) $request->post('og_image', '')),
                'robots'          => $request->post('robots') === 'noindex,nofollow' ? 'noindex,nofollow' : 'index,follow',
            ],
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'title'  => 'required|string|min:2|max:200',
            'slug'   => 'required|slug|max:200',
            'status' => 'required|in:draft,published,archived',
        ], ['title' => 'título', 'slug' => 'slug', 'status' => 'status']);

        $errors = $v->errors();
        if (!isset($errors['slug']) && $this->pages->slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'Já existe uma página com este slug.';
        }
        return $errors;
    }
}
