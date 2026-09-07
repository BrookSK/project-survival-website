<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\GameApi\ErrorPresenter;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\Exceptions\NotFoundException;
use App\Services\GameApi\GameApiConfig;
use App\Services\GameApi\GameStoreService;
use App\Services\GameApi\PlayerSession;

/**
 * Loja pública (catálogo da API do jogo).
 *
 * Visitantes veem o catálogo; jogadores logados recebem também `owned` por
 * produto (buscado ao vivo). A compra apenas inicia um pedido `pending` — sem
 * cobrança e sem concessão (a API não cobra). Nunca simula sucesso de compra.
 */
class StoreController extends Controller
{
    private GameStoreService $store;

    public function __construct()
    {
        $this->store = new GameStoreService();
    }

    private function ensureEnabled(): void
    {
        if (!GameApiConfig::isEnabled()) {
            $this->abort(404);
        }
    }

    public function index(Request $request): void
    {
        $this->ensureEnabled();

        $token = PlayerSession::accessToken();
        $offline = false;
        $categories = [];
        $products = [];

        try {
            $categories = $this->store->categories();
        } catch (GameApiException $e) {
            $offline = true;
        }

        try {
            // Se logado, busca com token para trazer `owned` (ao vivo).
            $products = $this->store->products($token);
        } catch (GameApiException $e) {
            $offline = true;
        }

        // Filtro de categoria opcional (client-side simples via slug).
        $activeCategory = (string) $request->query('categoria', '');

        $this->viewSite('site.store.index', [
            'title'          => 'Loja',
            'categories'     => $categories,
            'products'       => $products,
            'activeCategory' => $activeCategory,
            'offline'        => $offline,
            'loggedIn'       => $token !== null,
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $this->ensureEnabled();

        $idOrSlug = (string) ($params['id'] ?? '');
        $token = PlayerSession::accessToken();

        try {
            $product = $this->store->product($idOrSlug, $token);
        } catch (NotFoundException $e) {
            $this->abort(404);
            return;
        } catch (GameApiException $e) {
            Session::flash('error', ErrorPresenter::message($e, 'A loja'));
            $this->redirect('/loja');
            return;
        }

        $this->viewSite('site.store.product', [
            'title'    => $product['name'] ?? 'Produto',
            'product'  => $product,
            'loggedIn' => $token !== null,
        ]);
    }

    /**
     * Inicia um pedido (autenticado). NÃO cobra e NÃO concede o produto.
     */
    public function purchase(Request $request): void
    {
        $this->ensureEnabled();
        $this->verifyCsrf($request);

        $token = PlayerSession::accessToken();
        if ($token === null) {
            Session::set('__player_intended', '/loja');
            Session::flash('error', 'Entre na sua conta para iniciar um pedido.');
            $this->redirect('/login');
            return;
        }

        $productId = trim((string) $request->post('product_id', ''));
        if ($productId === '') {
            Session::flash('error', 'Produto inválido.');
            $this->back('/loja');
            return;
        }

        try {
            $order = PlayerSession::withAuth(fn ($t) => $this->store->purchase($t, $productId));
        } catch (GameApiException $e) {
            Session::flash('error', ErrorPresenter::message($e, 'A loja'));
            $this->back('/loja');
            return;
        }

        // Deixa claro: pedido apenas iniciado, sem cobrança nem concessão.
        $orderId = $order['order_id'] ? (' (nº ' . $order['order_id'] . ')') : '';
        Session::flash('info', 'Pedido iniciado' . $orderId . '. O pedido está pendente e não houve cobrança real. '
            . 'O item não é concedido até que o pagamento seja confirmado pelo backend.');
        $this->back('/loja');
    }
}
