<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

/**
 * Dashboard administrativo: visão geral, atalhos e atividade recente.
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->authorize('dashboard.view');

        $db = Database::getInstance();

        $stats = [
            'news_published' => $this->safeCount($db, 'news', "WHERE `status` = 'published' AND `deleted_at` IS NULL"),
            'news_draft'     => $this->safeCount($db, 'news', "WHERE `status` = 'draft' AND `deleted_at` IS NULL"),
            'pages'          => $this->safeCount($db, 'pages', "WHERE `deleted_at` IS NULL"),
            'media'          => $this->safeCount($db, 'media'),
            'messages'       => $this->safeCount($db, 'contact_messages', "WHERE `status` = 'new'"),
            'users'          => $this->safeCount($db, 'users'),
        ];

        $recentMessages = [];
        try {
            $recentMessages = $db->fetchAll(
                "SELECT `id`, `name`, `subject`, `status`, `created_at`
                 FROM `contact_messages` ORDER BY `created_at` DESC LIMIT 5"
            );
        } catch (\Throwable $e) {
        }

        $recentAudit = [];
        try {
            $recentAudit = $db->fetchAll(
                "SELECT `user_name`, `action`, `module`, `description`, `created_at`
                 FROM `audit_logs` ORDER BY `created_at` DESC LIMIT 8"
            );
        } catch (\Throwable $e) {
        }

        // Série simples: notícias publicadas nos últimos 6 meses (gráfico útil)
        $newsChart = $this->newsPerMonth($db);

        $this->viewAdmin('admin.dashboard.index', [
            'title'          => 'Dashboard',
            'stats'          => $stats,
            'recentMessages' => $recentMessages,
            'recentAudit'    => $recentAudit,
            'newsChart'      => $newsChart,
        ]);
    }

    private function safeCount(Database $db, string $table, string $where = ''): int
    {
        try {
            return (int) $db->fetchColumn("SELECT COUNT(*) FROM `{$table}` {$where}");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Notícias publicadas por mês (últimos 6 meses) para o mini-gráfico.
     */
    private function newsPerMonth(Database $db): array
    {
        $result = [];
        // Inicializa os 6 meses com zero (ordem cronológica)
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-{$i} months"));
            $result[$key] = 0;
        }

        try {
            $rows = $db->fetchAll(
                "SELECT DATE_FORMAT(`published_at`, '%Y-%m') AS ym, COUNT(*) AS total
                 FROM `news`
                 WHERE `status` = 'published' AND `published_at` >= (NOW() - INTERVAL 6 MONTH) AND `deleted_at` IS NULL
                 GROUP BY ym"
            );
            foreach ($rows as $row) {
                if (isset($result[$row['ym']])) {
                    $result[$row['ym']] = (int) $row['total'];
                }
            }
        } catch (\Throwable $e) {
        }

        return $result;
    }
}
