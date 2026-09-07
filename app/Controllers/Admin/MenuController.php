<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * Gerenciamento de menus e seus itens (por localização).
 */
class MenuController extends Controller
{
    private Menu $menus;
    private MenuItem $items;

    public function __construct()
    {
        $this->menus = new Menu();
        $this->items = new MenuItem();
    }

    public function index(Request $request): void
    {
        $this->authorize('menus.view');

        $menus = $this->menus->all('location');
        $itemsByMenu = [];
        foreach ($menus as $menu) {
            $itemsByMenu[$menu['id']] = $this->items->byMenu((int) $menu['id']);
        }

        $this->viewAdmin('admin.menus.index', [
            'title'       => 'Menus',
            'breadcrumbs' => [['label' => 'Menus']],
            'menus'       => $menus,
            'itemsByMenu' => $itemsByMenu,
            'errors'      => errors(),
        ]);
    }

    public function storeItem(Request $request): void
    {
        $this->authorize('menus.edit');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/menus');
            return;
        }

        $id = $this->items->create($data);
        Menu::flushCache();
        AuditService::log('create', 'menus', (string) $id, "Adicionou item de menu: {$data['label']}");
        Session::flash('success', 'Item adicionado ao menu.');
        $this->redirect('/admin/menus');
    }

    public function updateItem(Request $request, array $params): void
    {
        $this->authorize('menus.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->items->find($id)) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/menus');
            return;
        }

        $this->items->update($id, $data);
        Menu::flushCache();
        AuditService::log('update', 'menus', (string) $id, "Editou item de menu: {$data['label']}");
        Session::flash('success', 'Item atualizado.');
        $this->redirect('/admin/menus');
    }

    public function destroyItem(Request $request, array $params): void
    {
        $this->authorize('menus.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->items->find($id);
        if (!$item) {
            $this->abort(404);
        }
        $this->items->delete($id);
        Menu::flushCache();
        AuditService::log('delete', 'menus', (string) $id, "Removeu item de menu: {$item['label']}");
        Session::flash('success', 'Item removido.');
        $this->redirect('/admin/menus');
    }

    private function collect(Request $request): array
    {
        return [
            'menu_id'    => (int) $request->post('menu_id', 0),
            'label'      => trim((string) $request->post('label', '')),
            'url'        => trim((string) $request->post('url', '')),
            'target'     => $request->post('target') === '_blank' ? '_blank' : '_self',
            'is_active'  => $request->post('is_active') ? 1 : 0,
            'sort_order' => (int) $request->post('sort_order', 0),
        ];
    }

    private function validate(array $data): array
    {
        $v = new Validator($data, [
            'menu_id' => 'required|integer',
            'label'   => 'required|string|max:120',
            'url'     => 'required|string|max:255',
        ], ['label' => 'rótulo', 'url' => 'URL']);
        $errors = $v->errors();
        if (!isset($errors['menu_id']) && !$this->menus->find((int) $data['menu_id'])) {
            $errors['menu_id'] = 'Menu inválido.';
        }
        return $errors;
    }
}
