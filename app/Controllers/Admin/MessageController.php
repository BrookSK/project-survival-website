<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\ContactMessage;
use App\Services\AuditService;

/**
 * Visualização e gestão de mensagens de contato recebidas.
 */
class MessageController extends Controller
{
    private ContactMessage $messages;

    public function __construct()
    {
        $this->messages = new ContactMessage();
    }

    public function index(Request $request): void
    {
        $this->authorize('messages.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) setting('items_per_page', 15);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $result = $this->messages->paginate($page, $perPage, $search, $status ?: null);

        $this->viewAdmin('admin.messages.index', [
            'title'       => 'Mensagens',
            'breadcrumbs' => [['label' => 'Mensagens']],
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'search'      => $search,
            'status'      => $status,
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $this->authorize('messages.view');
        $item = $this->messages->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }

        // Marca como lida automaticamente ao abrir
        if ($item['status'] === 'new' && has_permission('messages.edit')) {
            $this->messages->updateStatus((int) $item['id'], 'read');
            $item['status'] = 'read';
        }

        $this->viewAdmin('admin.messages.show', [
            'title'       => 'Mensagem',
            'breadcrumbs' => [['label' => 'Mensagens', 'url' => '/admin/mensagens'], ['label' => 'Detalhe']],
            'message'     => $item,
        ]);
    }

    public function updateStatus(Request $request, array $params): void
    {
        $this->authorize('messages.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->messages->find($id)) {
            $this->abort(404);
        }

        $status = (string) $request->post('status', '');
        $this->messages->updateStatus($id, $status);
        AuditService::log('update', 'messages', (string) $id, "Alterou status da mensagem para: {$status}");
        Session::flash('success', 'Status atualizado.');
        $this->redirect('/admin/mensagens/' . $id);
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('messages.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->messages->find($id)) {
            $this->abort(404);
        }
        $this->messages->delete($id);
        AuditService::log('delete', 'messages', (string) $id, 'Excluiu uma mensagem de contato');
        Session::flash('success', 'Mensagem excluída.');
        $this->redirect('/admin/mensagens');
    }
}
