<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuditService;

/**
 * Visualização somente-leitura dos logs de auditoria.
 */
class AuditController extends Controller
{
    public function index(Request $request): void
    {
        $this->authorize('audit.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) setting('items_per_page', 15);
        $filters = [
            'module' => trim((string) $request->query('module', '')),
            'action' => trim((string) $request->query('action', '')),
        ];

        $result = AuditService::paginate($page, $perPage, $filters);

        $this->viewAdmin('admin.audit.index', [
            'title'       => 'Auditoria',
            'breadcrumbs' => [['label' => 'Auditoria']],
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'filters'     => $filters,
        ]);
    }
}
