<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Services\NotificationService;

/**
 * Notificações administrativas (listagem, marcar como lidas).
 */
class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $notifications = NotificationService::recent(50);
        NotificationService::markAllRead();

        $this->viewAdmin('admin.notifications.index', [
            'title'         => 'Notificações',
            'breadcrumbs'   => [['label' => 'Notificações']],
            'notifications' => $notifications,
        ]);
    }

    /**
     * Endpoint AJAX: retorna contador + notificações recentes (para o sino).
     */
    public function feed(Request $request): void
    {
        $this->json([
            'count' => NotificationService::unreadCount(),
            'items' => NotificationService::recent(8),
        ]);
    }

    public function markAllRead(Request $request): void
    {
        $this->verifyCsrf($request);
        NotificationService::markAllRead();
        $this->json(['ok' => true]);
    }
}
