<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Order;
use App\Models\PrivacyPolicy;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\PricingException;
use App\Services\Commerce\ProductPricing;
use App\Services\GameApi\ErrorPresenter;
use App\Services\GameApi\Exceptions\GameApiException;
use App\Services\GameApi\PlayerSession;
use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;

/**
 * Checkout comercial.
 *
 * Fluxo (server-side, sem confiar no navegador para preço/status):
 *   1. GET  /checkout?produto=..   -> mostra resumo com preço OFICIAL da API.
 *   2. POST /checkout              -> cria pedido (snapshot) + cria pagamento.
 *   3. GET  /checkout/{ref}/pagamento -> instruções PIX/cartão (consulta backend).
 *   4. GET  /checkout/{ref}/status    -> estado REAL (consulta gateway).
 *
 * Exige login. NUNCA cobra/concede fora do gateway/Game API.
 */
class CheckoutController extends Controller
{
    private CheckoutService $checkout;
    private ProductPricing $pricing;
    private Order $orders;

    public function __construct()
    {
        $this->checkout = new CheckoutService();
        $this->pricing = new ProductPricing();
        $this->orders = new Order();
    }

    private function ensureEnabled(): void
    {
        if (!PaymentConfig::enabled()) {
            Session::flash('info', 'A loja está temporariamente indisponível.');
            $this->redirect('/loja');
            exit;
        }
    }

    /**
     * Resumo do checkout: preço/moeda vêm da Game API (ao vivo). O navegador
     * não informa preço — só o id do produto e a quantidade.
     */
    public function form(Request $request): void
    {
        $this->ensureEnabled();

        $productId = trim((string) $request->query('produto', ''));
        $quantity = max(1, min(99, (int) $request->query('qtd', 1)));
        if ($productId === '') {
            $this->redirect('/loja');
            return;
        }

        $token = PlayerSession::accessToken();
        try {
            $priced = $this->pricing->resolve($productId, $token);
        } catch (PricingException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/loja');
            return;
        }

        $purchaseTerms = (new PrivacyPolicy())->publishedBySlug('termos-de-compra');
        $refundTerms = (new PrivacyPolicy())->publishedBySlug('reembolso');

        $this->viewSite('site.checkout.form', [
            'title'         => 'Finalizar compra',
            'product'       => $priced,
            'quantity'      => $quantity,
            'publicKey'     => PaymentConfig::mercadoPagoPublicKey(),
            'gateway'       => PaymentConfig::gateway(),
            'currency'      => $priced['currency'],
            'purchaseTerms' => $purchaseTerms,
            'refundTerms'   => $refundTerms,
        ]);
    }

    /**
     * Cria o pedido (snapshot) e o pagamento no gateway.
     */
    public function process(Request $request): void
    {
        $this->ensureEnabled();
        $this->verifyCsrf($request);

        $playerId = PlayerSession::userId();
        $token = PlayerSession::accessToken();
        if ($playerId === null || $token === null) {
            Session::set('__player_intended', '/loja');
            Session::flash('error', 'Entre na sua conta para finalizar a compra.');
            $this->redirect('/login');
            return;
        }

        $productId = trim((string) $request->post('product_id', ''));
        $quantity = max(1, min(99, (int) $request->post('quantity', 1)));
        $method = in_array($request->post('method'), ['pix', 'credit_card'], true) ? (string) $request->post('method') : 'pix';
        $coupon = trim((string) $request->post('coupon', ''));

        if ($productId === '') {
            Session::flash('error', 'Produto inválido.');
            $this->back('/loja');
            return;
        }

        // Aceite obrigatório dos termos de compra.
        if (!$request->has('accept_terms')) {
            Session::flash('error', 'É necessário aceitar os Termos de Compra para continuar.');
            $this->back('/checkout?produto=' . rawurlencode($productId));
            return;
        }

        $purchaseTerms = (new PrivacyPolicy())->publishedBySlug('termos-de-compra');
        $refundTerms = (new PrivacyPolicy())->publishedBySlug('reembolso');

        $user = PlayerSession::user();
        try {
            $order = $this->checkout->createOrder($playerId, $productId, $quantity, $token, [
                'email'                => $user['email'] ?? null,
                'ip'                   => $request->ip(),
                'user_agent'           => $request->userAgent(),
                'coupon'               => $coupon,
                'gateway'              => PaymentConfig::gateway(),
                'terms_version'        => $purchaseTerms['version']['version'] ?? null,
                'refund_terms_version' => $refundTerms['version']['version'] ?? null,
            ]);
        } catch (PricingException $e) {
            Session::flash('error', $e->getMessage());
            $this->back('/loja');
            return;
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível criar o pedido. Tente novamente.');
            $this->back('/loja');
            return;
        }

        // Cria o pagamento no gateway (server-side). Nunca simula aprovação.
        try {
            $payment = new PaymentService();
            $options = [];
            if ($method === 'credit_card') {
                $options['card_token'] = (string) $request->post('card_token', '');
                $options['installments'] = (int) $request->post('installments', 1);
                $options['payment_method_id'] = (string) $request->post('payment_method_id', '');
            }
            $payment->createForOrder($order, $method, $options);
        } catch (PaymentException $e) {
            Session::flash('error', 'Não foi possível iniciar o pagamento: ' . $e->getMessage());
            $this->redirect('/conta/pedidos/' . rawurlencode($order['reference']));
            return;
        }

        $this->redirect('/checkout/' . rawurlencode($order['reference']) . '/pagamento');
    }

    /**
     * Instruções de pagamento (PIX copia-e-cola/QR ou retorno do cartão).
     * Consulta o backend para exibir o estado real — nunca confia na tela.
     */
    public function payment(Request $request, array $params): void
    {
        $this->ensureEnabled();
        $reference = (string) ($params['reference'] ?? '');
        $order = $this->ownedOrder($reference);
        if ($order === null) {
            return;
        }

        $tx = (new \App\Models\PaymentTransaction())->forOrder((int) $order['id']);
        $latest = $tx ? end($tx) : null;

        $this->viewSite('site.checkout.payment', [
            'title'       => 'Pagamento do pedido ' . $order['reference'],
            'order'       => $order,
            'transaction' => $latest,
        ]);
    }

    /**
     * Consulta o estado REAL do pagamento no gateway e sincroniza. Usado pela
     * página de pagamento (polling) e ao retornar do checkout do cartão.
     */
    public function status(Request $request, array $params): void
    {
        $this->ensureEnabled();
        $reference = (string) ($params['reference'] ?? '');
        $order = $this->ownedOrder($reference, false);
        if ($order === null) {
            $this->json(['error' => true], 404);
            return;
        }

        $tx = (new \App\Models\PaymentTransaction())->forOrder((int) $order['id']);
        $latest = $tx ? end($tx) : null;

        // Consulta o gateway (fonte de verdade). Não concede itens aqui.
        if ($latest && !empty($latest['external_id'])) {
            try {
                (new PaymentService())->syncFromGateway((int) $order['id'], (string) $latest['external_id']);
                $order = $this->orders->findByReference($reference) ?? $order;
            } catch (PaymentException $e) {
                // Mantém o último estado conhecido; não inventa aprovação.
            }
        }

        $this->json([
            'reference'          => $order['reference'],
            'order_status'       => $order['order_status'],
            'payment_status'     => $order['payment_status'],
            'fulfillment_status' => $order['fulfillment_status'],
        ]);
    }

    /**
     * Carrega um pedido garantindo que pertence ao jogador logado (anti-IDOR).
     */
    private function ownedOrder(string $reference, bool $renderErrors = true): ?array
    {
        $playerId = PlayerSession::userId();
        $order = $reference !== '' ? $this->orders->findByReference($reference) : null;

        if (!$order || $playerId === null || (string) $order['player_id'] !== (string) $playerId) {
            if ($renderErrors) {
                $this->abort(404);
            }
            return null;
        }
        $order['items'] = $this->orders->items((int) $order['id']);
        return $order;
    }
}
