<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Coupon;
use App\Services\AuditService;
use App\Services\AuthService;

/**
 * CRUD de cupons de desconto (admin). Validação real de valores é feita no
 * checkout (backend); aqui é a gestão. Exige store.coupons.
 */
class CouponController extends Controller
{
    private Coupon $coupons;

    public function __construct()
    {
        $this->coupons = new Coupon();
    }

    public function index(Request $request): void
    {
        $this->authorize('store.coupons');
        $page = (int) $request->query('page', 1);
        $result = $this->coupons->paginate($page, 20);

        $this->viewAdmin('admin.store.coupons.index', [
            'title'       => 'Cupons',
            'breadcrumbs' => [['label' => 'Loja', 'url' => '/admin/loja'], ['label' => 'Cupons']],
            'result'      => $result,
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('store.coupons');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        if ($data === null) {
            $this->redirect('/admin/loja/cupons');
            return;
        }

        $data['created_by'] = (int) (AuthService::user()['id'] ?? 0) ?: null;
        $id = $this->coupons->create($data);
        AuditService::log('create', 'store_coupon', (string) $id, "Criou cupom {$data['code']}");
        Session::flash('success', 'Cupom criado.');
        $this->redirect('/admin/loja/cupons');
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('store.coupons');
        $this->verifyCsrf($request);

        $coupon = $this->coupons->find((int) ($params['id'] ?? 0));
        if (!$coupon) {
            $this->abort(404);
            return;
        }
        $data = $this->collect($request, (int) $coupon['id']);
        if ($data === null) {
            $this->redirect('/admin/loja/cupons');
            return;
        }
        $this->coupons->update((int) $coupon['id'], $data);
        AuditService::log('update', 'store_coupon', (string) $coupon['id'], "Atualizou cupom {$data['code']}");
        Session::flash('success', 'Cupom atualizado.');
        $this->redirect('/admin/loja/cupons');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('store.coupons');
        $this->verifyCsrf($request);

        $coupon = $this->coupons->find((int) ($params['id'] ?? 0));
        if (!$coupon) {
            $this->abort(404);
            return;
        }
        $this->coupons->delete((int) $coupon['id']);
        AuditService::log('delete', 'store_coupon', (string) $coupon['id'], "Removeu cupom {$coupon['code']}");
        Session::flash('success', 'Cupom removido.');
        $this->redirect('/admin/loja/cupons');
    }

    /**
     * Coleta e valida os dados do formulário. Retorna null (com flash) se inválido.
     */
    private function collect(Request $request, ?int $ignoreId = null): ?array
    {
        $code = strtoupper(trim((string) $request->post('code', '')));
        $type = $request->post('type') === 'fixed' ? 'fixed' : 'percent';
        $currency = strtoupper(substr(trim((string) $request->post('currency', 'BRL')), 0, 3)) ?: 'BRL';

        if ($code === '' || !preg_match('/^[A-Z0-9_-]{2,40}$/', $code)) {
            Session::flash('error', 'Código de cupom inválido (2-40 caracteres, A-Z 0-9 _ -).');
            return null;
        }

        $percentOff = null;
        $amountOff = null;
        if ($type === 'percent') {
            $percentOff = (float) $request->post('percent_off', 0);
            if ($percentOff <= 0 || $percentOff > 100) {
                Session::flash('error', 'Percentual deve estar entre 0 e 100.');
                return null;
            }
        } else {
            $amountOff = (int) round(((float) $request->post('amount_off', 0)) * 100);
            if ($amountOff <= 0) {
                Session::flash('error', 'Valor do desconto deve ser maior que zero.');
                return null;
            }
        }

        return [
            'code'             => $code,
            'description'      => trim((string) $request->post('description', '')) ?: null,
            'type'             => $type,
            'percent_off'      => $percentOff,
            'amount_off_cents' => $amountOff,
            'currency'         => $currency,
            'min_total_cents'  => (int) round(((float) $request->post('min_total', 0)) * 100),
            'max_redemptions'  => ($v = (int) $request->post('max_redemptions', 0)) > 0 ? $v : null,
            'per_player_limit' => ($v = (int) $request->post('per_player_limit', 0)) > 0 ? $v : null,
            'active'           => $request->has('active') ? 1 : 0,
            'starts_at'        => trim((string) $request->post('starts_at', '')) ?: null,
            'ends_at'          => trim((string) $request->post('ends_at', '')) ?: null,
        ];
    }
}
