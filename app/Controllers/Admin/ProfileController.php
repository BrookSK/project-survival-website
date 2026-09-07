<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Validators\Validator;

/**
 * Perfil do próprio usuário autenticado (dados e senha).
 */
class ProfileController extends Controller
{
    public function edit(Request $request): void
    {
        $user = auth_user();
        $this->viewAdmin('admin.profile.edit', [
            'title'       => 'Meu perfil',
            'breadcrumbs' => [['label' => 'Meu perfil']],
            'user'        => $user,
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request): void
    {
        $this->verifyCsrf($request);
        $current = auth_user();
        $data = $request->only(['name', 'email']);

        $validator = new Validator($data, [
            'name'  => 'required|string|min:2|max:150',
            'email' => 'required|email|max:190|unique:users,email,' . (int) $current['id'],
        ], ['name' => 'nome', 'email' => 'e-mail']);

        if ($validator->fails()) {
            $this->redirectWithErrors($validator->errors(), $data, '/admin/perfil');
            return;
        }

        (new User())->update((int) $current['id'], [
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        AuditService::log('update', 'profile', (string) $current['id'], 'Atualizou o próprio perfil');
        Session::flash('success', 'Perfil atualizado com sucesso.');
        $this->redirect('/admin/perfil');
    }

    public function updatePassword(Request $request): void
    {
        $this->verifyCsrf($request);
        $current = auth_user();
        $data = $request->only(['current_password', 'password', 'password_confirmation']);

        $validator = new Validator($data, [
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ], ['current_password' => 'senha atual', 'password' => 'nova senha']);

        if ($validator->fails()) {
            $this->redirectWithErrors($validator->errors(), [], '/admin/perfil');
            return;
        }

        // Confere a senha atual
        $userModel = new User();
        $full = $userModel->find((int) $current['id']);
        if (!$full || !password_verify($data['current_password'], $full['password'])) {
            $this->redirectWithErrors(['current_password' => 'A senha atual está incorreta.'], [], '/admin/perfil');
            return;
        }

        $userModel->updatePassword((int) $current['id'], AuthService::hash($data['password']));
        AuditService::log('update', 'profile', (string) $current['id'], 'Alterou a própria senha');
        Session::flash('success', 'Senha alterada com sucesso.');
        $this->redirect('/admin/perfil');
    }
}
