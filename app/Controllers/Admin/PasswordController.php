<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Validators\Validator;

/**
 * Recuperação e redefinição de senha.
 *
 * Por segurança, a solicitação sempre responde com sucesso genérico,
 * sem revelar se o e-mail existe (evita enumeração de contas).
 */
class PasswordController extends Controller
{
    public function showRequest(Request $request): void
    {
        Response::html(View::render('admin.auth.forgot', [
            'title'  => 'Recuperar senha',
            'errors' => errors(),
        ]));
    }

    public function sendReset(Request $request): void
    {
        $this->verifyCsrf($request);

        $email = (string) $request->post('email', '');
        $validator = new Validator(['email' => $email], ['email' => 'required|email'], ['email' => 'e-mail']);

        if ($validator->fails()) {
            $this->redirectWithErrors($validator->errors(), ['email' => $email], '/admin/esqueci-senha');
            return;
        }

        // Rate limiting por IP: máximo de 5 solicitações a cada 15 minutos.
        $rlKey = 'pwreset:' . $request->ip();
        if (\App\Services\RateLimiter::tooManyAttempts($rlKey, 5)) {
            \App\Core\Logger::warning('Rate limit de recuperação de senha atingido: ' . $request->ip());
            Session::flash('success', 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.');
            $this->redirect('/admin/login');
            return;
        }
        \App\Services\RateLimiter::hit($rlKey, 900);

        $user = (new User())->findByEmail($email);

        if ($user && (int) $user['is_active'] === 1) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = (int) Config::get('app.security.password_reset_expires', 60);

            (new PasswordReset())->createFor($email, $tokenHash, $expires);

            $link = url('admin/redefinir-senha/' . $token);
            $siteName = setting('site_name', 'Site');
            $html = "<p>Você solicitou a redefinição de senha em <strong>" . e($siteName) . "</strong>.</p>"
                  . "<p>Clique no link abaixo para criar uma nova senha (válido por {$expires} minutos):</p>"
                  . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
                  . '<p>Se você não fez esta solicitação, ignore este e-mail.</p>';

            (new EmailService())->send($email, $user['name'], "Redefinição de senha - {$siteName}", $html);
        }

        Session::flash('success', 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.');
        Response::redirect('/admin/login');
    }

    public function showReset(Request $request, array $params): void
    {
        $token = $params['token'] ?? '';
        $tokenHash = hash('sha256', $token);
        $record = (new PasswordReset())->findValidByHash($tokenHash);

        if (!$record) {
            Session::flash('error', 'Link de recuperação inválido ou expirado.');
            Response::redirect('/admin/esqueci-senha');
            return;
        }

        Response::html(View::render('admin.auth.reset', [
            'title'  => 'Redefinir senha',
            'token'  => $token,
            'errors' => errors(),
        ]));
    }

    public function reset(Request $request): void
    {
        $this->verifyCsrf($request);

        $data = $request->only(['token', 'password', 'password_confirmation']);
        $token = (string) ($data['token'] ?? '');

        $validator = new Validator($data, [
            'password' => 'required|min:8|confirmed',
        ], ['password' => 'senha']);

        if ($validator->fails()) {
            Session::set('__errors', $validator->errors());
            Response::redirect('/admin/redefinir-senha/' . urlencode($token));
            return;
        }

        $tokenHash = hash('sha256', $token);
        $resetModel = new PasswordReset();
        $record = $resetModel->findValidByHash($tokenHash);

        if (!$record) {
            Session::flash('error', 'Link de recuperação inválido ou expirado.');
            Response::redirect('/admin/esqueci-senha');
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($record['email']);

        if ($user) {
            $userModel->updatePassword((int) $user['id'], AuthService::hash($data['password']));
            $resetModel->markUsed((int) $record['id']);
        }

        Session::flash('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
        Response::redirect('/admin/login');
    }
}
