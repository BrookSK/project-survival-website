<?php

namespace App\Core;

/**
 * Controller base.
 *
 * Fornece atalhos comuns: renderizar views, redirecionar, responder JSON,
 * validar CSRF e abortar com códigos de erro.
 */
abstract class Controller
{
    /**
     * Renderiza uma view com layout e envia como resposta HTML.
     */
    protected function view(string $view, array $data = [], ?string $layout = null): void
    {
        Response::html(View::render($view, $data, $layout));
    }

    /**
     * Renderiza usando o layout do site público.
     */
    protected function viewSite(string $view, array $data = []): void
    {
        $this->view($view, $data, 'layouts.site');
    }

    /**
     * Renderiza usando o layout do painel administrativo.
     */
    protected function viewAdmin(string $view, array $data = []): void
    {
        $this->view($view, $data, 'layouts.admin');
    }

    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    protected function back(string $fallback = '/'): void
    {
        Response::back($fallback);
    }

    /**
     * Valida o token CSRF da requisição; aborta em caso de falha.
     */
    protected function verifyCsrf(Request $request): void
    {
        if (!Csrf::validate($request->csrfToken())) {
            Session::flash('error', 'Sessão expirada ou inválida. Tente novamente.');
            if ($request->wantsJson()) {
                Response::json(['error' => true, 'message' => 'Token CSRF inválido.'], 419);
                exit;
            }
            Response::back();
        }
    }

    protected function abort(int $code): void
    {
        (new Router())->abort($code, new Request());
        exit;
    }

    /**
     * Garante que o usuário autenticado possui a permissão; aborta com 403 caso contrário.
     */
    protected function authorize(string $permission): void
    {
        if (!\App\Services\AuthService::can($permission)) {
            $this->abort(403);
        }
    }

    /**
     * Redireciona de volta repopulando o formulário com erros e input antigo.
     */
    protected function redirectWithErrors(array $errors, array $input, string $fallback = '/'): void
    {
        Session::set('__errors', $errors);
        Session::flashInput($input);
        Response::back($fallback);
    }
}
