<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\SettingsService;
use App\Validators\Validator;

/**
 * Configurações do sistema, organizadas por categorias.
 *
 * As configurações vivem na tabela `settings`. Esta tela permite editá-las
 * em grupo e inclui o envio de um e-mail de teste para validar o SMTP.
 */
class SettingController extends Controller
{
    /**
     * Grupos exibidos, na ordem, com rótulos amigáveis.
     */
    private const GROUPS = [
        'general'        => 'Gerais',
        'email'          => 'E-mail',
        'seo'            => 'SEO',
        'social'         => 'Redes sociais',
        'cookies'        => 'Cookies / LGPD',
        'system'         => 'Sistema',
        'notifications'  => 'Notificações',
        'game_api'       => 'API do Jogo',
        'game_api_cache' => 'Cache da API',
        'releases'       => 'Releases / Download',
        'website_urls'   => 'URLs do site',
        'privacy'        => 'Privacidade',
        'payments'       => 'Pagamentos / Loja',
    ];

    public function index(Request $request): void
    {
        $this->authorize('settings.view');

        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT `group`, `key`, `value`, `type`, `is_secret`, `label`, `description`
             FROM `settings` ORDER BY `group` ASC, `id` ASC"
        );

        // Organiza por grupo
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group']][] = $row;
        }

        $active = (string) $request->query('grupo', 'general');
        if (!isset(self::GROUPS[$active])) {
            $active = 'general';
        }

        $this->viewAdmin('admin.settings.index', [
            'title'       => 'Configurações',
            'breadcrumbs' => [['label' => 'Configurações']],
            'groups'      => self::GROUPS,
            'settings'    => $grouped,
            'active'      => $active,
        ]);
    }

    public function update(Request $request): void
    {
        $this->authorize('settings.edit');
        $this->verifyCsrf($request);

        $group = (string) $request->post('__group', 'general');
        if (!isset(self::GROUPS[$group])) {
            $group = 'general';
        }

        $db = Database::getInstance();
        // Carrega apenas as chaves do grupo para saber o tipo e evitar gravar chaves arbitrárias
        $rows = $db->fetchAll(
            "SELECT `key`, `type`, `is_secret` FROM `settings` WHERE `group` = :g",
            ['g' => $group]
        );

        $submitted = $request->all();

        foreach ($rows as $row) {
            $key = $row['key'];

            // Booleanos: presença do checkbox
            if ($row['type'] === 'boolean') {
                SettingsService::set($key, !empty($submitted[$key]) ? '1' : '0');
                continue;
            }

            if (!array_key_exists($key, $submitted)) {
                continue;
            }

            $value = trim((string) $submitted[$key]);

            // Campos secretos vazios: mantém o valor atual (não sobrescreve senha SMTP)
            if ((int) $row['is_secret'] === 1 && $value === '') {
                continue;
            }

            SettingsService::set($key, $value);
        }

        SettingsService::flush();
        AuditService::log('update', 'settings', $group, "Atualizou configurações: {$group}");
        Session::flash('success', 'Configurações salvas com sucesso.');
        $this->redirect('/admin/configuracoes?grupo=' . $group);
    }

    /**
     * Envia um e-mail de teste usando as configurações SMTP atuais.
     */
    public function sendTestEmail(Request $request): void
    {
        $this->authorize('settings.edit');
        $this->verifyCsrf($request);

        $to = trim((string) $request->post('test_email', ''));
        $validator = new Validator(['email' => $to], ['email' => 'required|email'], ['email' => 'e-mail']);

        if ($validator->fails()) {
            Session::flash('error', 'Informe um e-mail de destino válido para o teste.');
            $this->redirect('/admin/configuracoes?grupo=email');
            return;
        }

        // Garante que o EmailService leia os valores mais recentes
        SettingsService::flush();
        $service = new EmailService();
        $ok = $service->sendTest($to);

        if ($ok) {
            Session::flash('success', "E-mail de teste enviado para {$to}.");
        } else {
            $err = $service->lastError() ? ' (' . $service->lastError() . ')' : '';
            Session::flash('error', 'Falha ao enviar o e-mail de teste. Verifique as configurações SMTP.' . $err);
        }

        AuditService::log('test', 'settings', 'email', "Enviou e-mail de teste para {$to}");
        $this->redirect('/admin/configuracoes?grupo=email');
    }
}
