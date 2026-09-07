<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Validators\Validator;

/**
 * Autenticação do painel administrativo (login/logout).
 */
class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        Response::html(\App\Core\View::render('admin.auth.login', [
            'title'  => 'Entrar',
            'errors' => errors(),
        ]));
    }

    public function login(Request $request): void
    {
        $this->verifyCsrf($request);

        $data = $request->only(['email', 'password']);

        $validator = new Validator($data, [
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email'    => 'e-mail',
            'password' => 'senha',
        ]);

        if ($validator->fails()) {
            $this->redirectWithErrors($validator->errors(), $data, '/admin/login');
            return;
        }

        $result = AuthService::attempt($data['email'], $data['password'], $request->ip());

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            Session::flashInput(['email' => $data['email']]);
            Response::redirect('/admin/login');
            return;
        }

        Session::flash('success', 'Bem-vindo(a) de volta!');

        $intended = Session::get('__intended', '/admin');
        Session::remove('__intended');
        Response::redirect($intended ?: '/admin');
    }

    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        AuthService::logout();
        Session::flash('success', 'Você saiu com segurança.');
        Response::redirect('/admin/login');
    }
}
