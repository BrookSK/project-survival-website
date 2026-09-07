<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\GameApi\ErrorPresenter;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameAuthService;
use App\Services\GameApi\PlayerSession;
use App\Services\ConsentService;
use App\Validators\Validator;

/**
 * Autenticação do jogador contra a API do jogo (login, registro, logout).
 *
 * A conta do jogador pertence à API — este site apenas coleta credenciais,
 * repassa ao endpoint oficial e guarda os tokens em sessão server-side. Nenhuma
 * senha de jogador é armazenada ou logada aqui.
 */
class AuthController extends Controller
{
    private GameAuthService $auth;

    public function __construct()
    {
        $this->auth = new GameAuthService();
    }

    /**
     * Garante que a integração está ativa; caso contrário, 404 (rotas de jogador
     * não fazem sentido sem a API).
     */
    private function ensureEnabled(): void
    {
        if (!GameApiConfig::isEnabled()) {
            $this->abort(404);
        }
    }

    public function loginForm(Request $request): void
    {
        $this->ensureEnabled();
        if (PlayerSession::check()) {
            $this->redirect('/conta');
            return;
        }
        $this->viewSite('site.auth.login', [
            'title'  => 'Entrar',
            'errors' => errors(),
        ]);
    }

    public function login(Request $request): void
    {
        $this->ensureEnabled();
        $this->verifyCsrf($request);

        $data = [
            'identifier' => trim((string) $request->post('identifier', '')),
            'password'   => (string) $request->post('password', ''),
        ];

        $v = new Validator($data, [
            'identifier' => 'required|string|max:150',
            'password'   => 'required|string|min:1',
        ], ['identifier' => 'e-mail ou usuário', 'password' => 'senha']);

        if ($v->fails()) {
            $this->redirectWithErrors($v->errors(), $data, '/login');
            return;
        }

        try {
            $tokens = $this->auth->login($data['identifier'], $data['password']);
        } catch (GameApiException $e) {
            Session::flash('error', ErrorPresenter::message($e, 'O login'));
            $this->redirectWithErrors([], $data, '/login');
            return;
        }

        if (empty($tokens['access_token'])) {
            Session::flash('error', 'Não foi possível autenticar. Tente novamente.');
            $this->redirect('/login');
            return;
        }

        PlayerSession::store($tokens);

        $intended = Session::get('__player_intended', '/conta');
        Session::remove('__player_intended');
        Session::flash('success', 'Bem-vindo de volta!');
        $this->redirect(is_string($intended) && $intended !== '' ? $intended : '/conta');
    }

    public function registerForm(Request $request): void
    {
        $this->ensureEnabled();
        if (PlayerSession::check()) {
            $this->redirect('/conta');
            return;
        }
        $this->viewSite('site.auth.register', [
            'title'  => 'Criar conta',
            'errors' => errors(),
        ]);
    }

    public function register(Request $request): void
    {
        $this->ensureEnabled();
        $this->verifyCsrf($request);

        $data = [
            'email'    => trim((string) $request->post('email', '')),
            'username' => trim((string) $request->post('username', '')),
            'password' => (string) $request->post('password', ''),
        ];
        // Aceite obrigatório (Termos + Privacidade); marketing é opcional.
        $acceptTerms = (bool) $request->post('accept_terms');
        $marketing = (bool) $request->post('marketing');

        $v = new Validator($data, [
            'email'    => 'required|email|max:190',
            'username' => 'required|string|min:3|max:40',
            'password' => 'required|string|min:8|max:100|confirmed',
        ], ['email' => 'e-mail', 'username' => 'usuário', 'password' => 'senha']);

        $errors = $v->errors();
        if (!$acceptTerms) {
            $errors['accept_terms'] = 'Você precisa aceitar os Termos de Uso e a Política de Privacidade.';
        }
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/criar-conta');
            return;
        }

        try {
            $this->auth->register($data['email'], $data['username'], $data['password']);
        } catch (GameApiException $e) {
            Session::flash('error', ErrorPresenter::message($e, 'O cadastro'));
            $this->redirectWithErrors([], $data, '/criar-conta');
            return;
        }

        // Conta criada na API. Tenta autenticar em seguida para já entrar.
        try {
            $tokens = $this->auth->login($data['email'], $data['password']);
            if (!empty($tokens['access_token'])) {
                PlayerSession::store($tokens);
                $this->recordConsents($request, $marketing);
                Session::flash('success', 'Conta criada com sucesso. Bem-vindo!');
                $this->redirect('/conta');
                return;
            }
        } catch (GameApiException $e) {
            // Registro OK mas login automático falhou: manda para o login.
        }

        Session::flash('success', 'Conta criada com sucesso. Faça login para continuar.');
        $this->redirect('/login');
    }

    /**
     * Registra os consentimentos do jogador recém-logado (obrigatórios na
     * versão vigente + marketing opcional). Requer sessão do jogador ativa.
     */
    private function recordConsents(Request $request, bool $marketing): void
    {
        $playerId = (string) (PlayerSession::userId() ?? '');
        if ($playerId === '') {
            return;
        }
        $consent = new ConsentService();
        $consent->recordRequiredAcceptance($playerId, 'register', $request->ip(), $request->userAgent());
        $consent->recordMarketing($playerId, $marketing, $request->ip(), $request->userAgent());
    }

    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        PlayerSession::logout();
        Session::flash('success', 'Você saiu da sua conta.');
        $this->redirect('/');
    }
}
