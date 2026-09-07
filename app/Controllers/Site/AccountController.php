<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\GameApi\ErrorPresenter;
use App\Services\GameApi\Exceptions\AuthenticationException;
use App\Services\GameApi\Exceptions\ConflictException;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\Exceptions\NotFoundException;
use App\Services\GameApi\Exceptions\RateLimitException;
use App\Services\GameApi\GameAuthService;
use App\Services\GameApi\GamePlayerService;
use App\Services\GameApi\PlayerSession;
use App\Validators\Validator;

/**
 * Área "Minha conta" do jogador (protegida por PlayerAuthMiddleware).
 *
 * Todas as leituras/escritas passam por PlayerSession::withAuth() para renovar
 * o token automaticamente uma única vez em caso de 401. Se a sessão expirar de
 * vez, o jogador é deslogado localmente e enviado ao login (sem loop).
 *
 * Nenhum dado privado do jogador é cacheado; a API é a fonte de verdade sobre
 * o que ele possui.
 */
class AccountController extends Controller
{
    private GamePlayerService $player;
    private GameAuthService $auth;

    public function __construct()
    {
        $this->player = new GamePlayerService();
        $this->auth = new GameAuthService();
    }

    /**
     * Visão geral: dados do jogador (GET /me).
     */
    public function overview(Request $request): void
    {
        try {
            $me = PlayerSession::withAuth(fn ($t) => $this->auth->me($t));
            if ($me !== []) {
                PlayerSession::setUser($me);
            }
        } catch (AuthenticationException $e) {
            $this->sessionExpired();
            return;
        } catch (GameApiException $e) {
            // Falha temporária: mostra os dados de sessão que já temos.
            $me = PlayerSession::user();
            Session::flash('error', 'Não foi possível atualizar seus dados. Tente novamente.');
        }

        $this->viewSite('site.account.overview', [
            'title' => 'Minha conta',
            'me'    => $me ?? PlayerSession::user(),
        ]);
    }

    /**
     * Inventário: entitlements (fonte de verdade) + inventário (owned/orders).
     */
    public function inventory(Request $request): void
    {
        $entitlements = [];
        $inventory = ['owned' => [], 'orders' => []];
        $error = null;

        try {
            $entitlements = PlayerSession::withAuth(fn ($t) => $this->player->entitlements($t));
            $inventory = PlayerSession::withAuth(fn ($t) => $this->player->inventory($t));
        } catch (AuthenticationException $e) {
            $this->sessionExpired();
            return;
        } catch (GameApiException $e) {
            $error = 'Não foi possível atualizar seus dados. Tente novamente.';
        }

        $this->viewSite('site.account.inventory', [
            'title'        => 'Inventário',
            'entitlements' => $entitlements,
            'owned'        => $inventory['owned'] ?? [],
            'orders'       => $inventory['orders'] ?? [],
            'error'        => $error,
        ]);
    }

    public function redeemForm(Request $request): void
    {
        $this->viewSite('site.account.redeem', [
            'title'  => 'Resgatar código',
            'errors' => errors(),
        ]);
    }

    /**
     * Resgata um código. Distingue claramente 404/409/429 e evita duplo envio.
     */
    public function redeem(Request $request): void
    {
        $this->verifyCsrf($request);

        $code = trim((string) $request->post('code', ''));
        $v = new Validator(['code' => $code], ['code' => 'required|string|max:64'], ['code' => 'código']);
        if ($v->fails()) {
            $this->redirectWithErrors($v->errors(), ['code' => $code], '/conta/resgatar');
            return;
        }

        try {
            $result = PlayerSession::withAuth(fn ($t) => $this->player->redeem($t, $code));
        } catch (AuthenticationException $e) {
            $this->sessionExpired();
            return;
        } catch (NotFoundException $e) {
            Session::flash('error', 'Código inválido. Verifique e tente novamente.');
            $this->redirect('/conta/resgatar');
            return;
        } catch (ConflictException $e) {
            // Expirado / esgotado / já utilizado — usa a mensagem da API se houver.
            Session::flash('error', $e->getMessage() ?: 'Este código não está mais disponível (expirado, esgotado ou já utilizado).');
            $this->redirect('/conta/resgatar');
            return;
        } catch (RateLimitException $e) {
            Session::flash('error', ErrorPresenter::message($e));
            $this->redirect('/conta/resgatar');
            return;
        } catch (GameApiException $e) {
            Session::flash('error', ErrorPresenter::message($e, 'O resgate'));
            $this->redirect('/conta/resgatar');
            return;
        }

        if (!empty($result['redeemed'])) {
            $detail = $result['value'] ? (': ' . $result['value']) : '';
            Session::flash('success', 'Código resgatado com sucesso' . $detail . '.');
        } else {
            Session::flash('info', 'Código processado.');
        }
        $this->redirect('/conta/inventario');
    }

    public function securityForm(Request $request): void
    {
        $this->viewSite('site.account.security', [
            'title'  => 'Segurança',
            'errors' => errors(),
        ]);
    }

    /**
     * Altera a senha do jogador (POST /auth/change-password na API).
     */
    public function changePassword(Request $request): void
    {
        $this->verifyCsrf($request);

        $data = [
            'current_password' => (string) $request->post('current_password', ''),
            'new_password'     => (string) $request->post('new_password', ''),
        ];
        $v = new Validator($data, [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|max:100|confirmed',
        ], ['current_password' => 'senha atual', 'new_password' => 'nova senha']);

        if ($v->fails()) {
            // Não repassa senhas para o old input (Session::flashInput já as remove).
            $this->redirectWithErrors($v->errors(), [], '/conta/seguranca');
            return;
        }

        try {
            PlayerSession::withAuth(function ($t) use ($data) {
                $this->auth->changePassword($t, $data['current_password'], $data['new_password']);
                return true;
            });
        } catch (AuthenticationException $e) {
            $this->sessionExpired();
            return;
        } catch (GameApiException $e) {
            Session::flash('error', ErrorPresenter::message($e));
            $this->redirect('/conta/seguranca');
            return;
        }

        Session::flash('success', 'Senha alterada com sucesso.');
        $this->redirect('/conta/seguranca');
    }

    /**
     * Sessão expirada: encerra localmente e envia ao login.
     */
    private function sessionExpired(): void
    {
        PlayerSession::forget();
        Session::flash('error', 'Sua sessão expirou. Entre novamente.');
        $this->redirect('/login');
    }
}
