<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * CRUD de perfis (roles) com atribuição de permissões.
 */
class RoleController extends Controller
{
    private Role $roles;
    private Permission $permissions;

    public function __construct()
    {
        $this->roles = new Role();
        $this->permissions = new Permission();
    }

    public function index(Request $request): void
    {
        $this->authorize('roles.view');
        $this->viewAdmin('admin.roles.index', [
            'title'       => 'Perfis',
            'breadcrumbs' => [['label' => 'Perfis']],
            'items'       => $this->roles->allWithCounts(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('roles.create');
        $this->viewAdmin('admin.roles.form', [
            'title'         => 'Novo perfil',
            'breadcrumbs'   => [['label' => 'Perfis', 'url' => '/admin/perfis'], ['label' => 'Novo']],
            'role_item'     => null,
            'groups'        => $this->permissions->grouped(),
            'selectedPerms' => [],
            'errors'        => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('roles.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/perfis/criar');
            return;
        }

        $id = $this->roles->create([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'],
            'is_system'   => 0,
        ]);
        $this->roles->syncPermissions($id, $data['permissions']);

        AuditService::log('create', 'roles', (string) $id, "Criou o perfil: {$data['name']}");
        Session::flash('success', 'Perfil criado com sucesso.');
        $this->redirect('/admin/perfis');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('roles.edit');
        $item = $this->roles->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.roles.form', [
            'title'         => 'Editar perfil',
            'breadcrumbs'   => [['label' => 'Perfis', 'url' => '/admin/perfis'], ['label' => 'Editar']],
            'role_item'     => $item,
            'groups'        => $this->permissions->grouped(),
            'selectedPerms' => $this->roles->permissionIds((int) $item['id']),
            'errors'        => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('roles.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->roles->find($id);
        if (!$item) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/perfis/{$id}/editar");
            return;
        }

        // Perfis de sistema mantêm o slug original
        $update = ['name' => $data['name'], 'description' => $data['description']];
        if ((int) $item['is_system'] === 0) {
            $update['slug'] = $data['slug'];
        }

        $this->roles->update($id, $update);

        // super-admin nunca perde permissões (acesso total é tratado no código)
        if ($item['slug'] !== 'super-admin') {
            $this->roles->syncPermissions($id, $data['permissions']);
        }

        AuditService::log('update', 'roles', (string) $id, "Editou o perfil: {$data['name']}");
        Session::flash('success', 'Perfil atualizado.');
        $this->redirect('/admin/perfis');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('roles.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->roles->find($id);
        if (!$item) {
            $this->abort(404);
        }

        if ((int) $item['is_system'] === 1) {
            Session::flash('error', 'Perfis de sistema não podem ser excluídos.');
            $this->redirect('/admin/perfis');
            return;
        }

        $this->roles->delete($id);
        AuditService::log('delete', 'roles', (string) $id, "Excluiu o perfil: {$item['name']}");
        Session::flash('success', 'Perfil excluído.');
        $this->redirect('/admin/perfis');
    }

    private function collect(Request $request): array
    {
        $name = trim((string) $request->post('name', ''));
        $slug = trim((string) $request->post('slug', ''));
        $perms = $request->post('permissions', []);
        return [
            'name'        => $name,
            'slug'        => $slug !== '' ? str_slug($slug) : str_slug($name),
            'description' => trim((string) $request->post('description', '')),
            'permissions' => is_array($perms) ? $perms : [],
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'name' => 'required|string|min:2|max:80',
            'slug' => 'required|slug|max:80',
        ], ['name' => 'nome', 'slug' => 'slug']);
        $errors = $v->errors();
        if (!isset($errors['slug']) && $this->roles->slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'Já existe um perfil com este slug.';
        }
        return $errors;
    }
}
