<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\NewsCategory;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * CRUD de categorias de notícias (gerenciamento inline na listagem).
 */
class CategoryController extends Controller
{
    private NewsCategory $categories;

    public function __construct()
    {
        $this->categories = new NewsCategory();
    }

    public function index(Request $request): void
    {
        $this->authorize('categories.view');
        $this->viewAdmin('admin.categories.index', [
            'title'       => 'Categorias',
            'breadcrumbs' => [['label' => 'Categorias']],
            'items'       => $this->categories->allWithCounts(),
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('categories.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/categorias');
            return;
        }

        $id = $this->categories->create($data);
        AuditService::log('create', 'categories', (string) $id, "Criou a categoria: {$data['name']}");
        Session::flash('success', 'Categoria criada com sucesso.');
        $this->redirect('/admin/categorias');
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('categories.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->categories->find($id)) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/categorias');
            return;
        }

        $this->categories->update($id, $data);
        AuditService::log('update', 'categories', (string) $id, "Editou a categoria: {$data['name']}");
        Session::flash('success', 'Categoria atualizada.');
        $this->redirect('/admin/categorias');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('categories.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->categories->find($id);
        if (!$item) {
            $this->abort(404);
        }

        $this->categories->delete($id);
        AuditService::log('delete', 'categories', (string) $id, "Excluiu a categoria: {$item['name']}");
        Session::flash('success', 'Categoria excluída.');
        $this->redirect('/admin/categorias');
    }

    private function collect(Request $request): array
    {
        $name = trim((string) $request->post('name', ''));
        $slug = trim((string) $request->post('slug', ''));
        return [
            'name'        => $name,
            'slug'        => $slug !== '' ? str_slug($slug) : str_slug($name),
            'description' => trim((string) $request->post('description', '')),
            'is_active'   => $request->post('is_active') !== null ? ($request->post('is_active') ? 1 : 0) : 1,
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'name' => 'required|string|min:2|max:120',
            'slug' => 'required|slug|max:120',
        ], ['name' => 'nome', 'slug' => 'slug']);
        $errors = $v->errors();
        if (!isset($errors['slug']) && $this->categories->slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'Já existe uma categoria com este slug.';
        }
        return $errors;
    }
}
