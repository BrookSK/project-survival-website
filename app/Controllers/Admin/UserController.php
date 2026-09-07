<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Validators\Validator;

/**
 * CRUD de usuários administrativos, com atribuição de perfis (roles).
 */
class UserController extends Controller
{
    private User $users;
    private Role $roles;

    public function __construct()
    {
        $this->users = new User();
        $this->roles = new Role();
    }

    public function index(Request $request): void
    {
        $this->authorize('users.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) setting('items_per_page', 15);
        $search = trim((string) $request->query('q', ''));

        $result = $this->users->paginate($page, $perPage, $search);

        // Carrega roles de cada usuário para exibição
        foreach ($result['items'] as &$u) {
            $u['role_names'] = array_column($this->users->roles((int) $u['id']), 'name');
        }
        unset($u);

        $this->viewAdmin('admin.users.index', [
            'title'       => 'Usuários',
            'breadcrumbs' => [['label' => 'Usuários']],
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'search'      => $search,
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('users.create');
        $this->viewAdmin('admin.users.form', [
            'title'         => 'Novo usuário',
            'breadcrumbs'   => [['label' => 'Usuários', 'url' => '/admin/usuarios'], ['label' => 'Novo']],
            'user_item'     => null,
            'allRoles'      => $this->roles->all('name'),
            'selectedRoles' => [],
            'errors'        => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('users.create');
        $this->verifyCsrf($request);

        $data = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $roleIds = $request->post('roles', []);
        $isActive = $request->post('is_active') ? 1 : 0;

        $v = new Validator($data, [
            'name'     => 'required|string|min:2|max:150',
            'email'    => 'required|email|max:190|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ], ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha']);

        if ($v->fails()) {
            $this->redirectWithErrors($v->errors(), $data, '/admin/usuarios/criar');
            return;
        }

        $id = $this->users->create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => AuthService::hash($data['password']),
            'is_active' => $isActive,
        ]);
        $this->users->syncRoles($id, is_array($roleIds) ? $roleIds : []);

        AuditService::log('create', 'users', (string) $id, "Criou o usuário: {$data['email']}");
        Session::flash('success', 'Usuário criado com sucesso.');
        $this->redirect('/admin/usuarios');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('users.edit');
        $item = $this->users->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }
        unset($item['password']);

        $this->viewAdmin('admin.users.form', [
            'title'         => 'Editar usuário',
            'breadcrumbs'   => [['label' => 'Usuários', 'url' => '/admin/usuarios'], ['label' => 'Editar']],
            'user_item'     => $item,
            'allRoles'      => $this->roles->all('name'),
            'selectedRoles' => $this->users->roleIds((int) $item['id']),
            'errors'        => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('users.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->users->find($id);
        if (!$item) {
            $this->abort(404);
        }

        $data = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $roleIds = $request->post('roles', []);
        $isActive = $request->post('is_active') ? 1 : 0;

        $rules = [
            'name'  => 'required|string|min:2|max:150',
            'email' => 'required|email|max:190|unique:users,email,' . $id,
        ];
        if (!empty($data['password'])) {
            $rules['password'] = 'min:8|confirmed';
        }

        $v = new Validator($data, $rules, ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha']);
        if ($v->fails()) {
            $this->redirectWithErrors($v->errors(), $data, "/admin/usuarios/{$id}/editar");
            return;
        }

        $update = ['name' => $data['name'], 'email' => $data['email'], 'is_active' => $isActive];
        if (!empty($data['password'])) {
            $update['password'] = AuthService::hash($data['password']);
        }

        // Impede o usuário de desativar a si mesmo
        if ($id === (int) auth_user()['id']) {
            $update['is_active'] = 1;
        }

        $this->users->update($id, $update);
        $this->users->syncRoles($id, is_array($roleIds) ? $roleIds : []);

        AuditService::log('update', 'users', (string) $id, "Editou o usuário: {$data['email']}");
        Session::flash('success', 'Usuário atualizado.');
        $this->redirect('/admin/usuarios');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('users.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->users->find($id);
        if (!$item) {
            $this->abort(404);
        }

        if ($id === (int) auth_user()['id']) {
            Session::flash('error', 'Você não pode excluir a própria conta.');
            $this->redirect('/admin/usuarios');
            return;
        }

        $this->users->delete($id);
        AuditService::log('delete', 'users', (string) $id, "Excluiu o usuário: {$item['email']}");
        Session::flash('success', 'Usuário excluído.');
        $this->redirect('/admin/usuarios');
    }
}
