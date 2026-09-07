<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\SocialLink;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * Gerenciamento das redes sociais (CRUD inline + reordenação).
 */
class SocialLinkController extends Controller
{
    /** Plataformas conhecidas (rótulo => ícone). */
    private const PLATFORMS = [
        'discord'   => 'Discord',
        'youtube'   => 'YouTube',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'x'         => 'X (Twitter)',
        'facebook'  => 'Facebook',
        'steam'     => 'Steam',
        'twitch'    => 'Twitch',
        'reddit'    => 'Reddit',
    ];

    private SocialLink $links;

    public function __construct()
    {
        $this->links = new SocialLink();
    }

    public function index(Request $request): void
    {
        $this->authorize('social.view');
        $this->viewAdmin('admin.social.index', [
            'title'       => 'Redes sociais',
            'breadcrumbs' => [['label' => 'Redes sociais']],
            'items'       => $this->links->allOrdered(),
            'platforms'   => self::PLATFORMS,
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('social.edit');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/redes-sociais');
            return;
        }

        $id = $this->links->create($data);
        AuditService::log('create', 'social', (string) $id, "Adicionou rede social: {$data['platform']}");
        Session::flash('success', 'Rede social adicionada.');
        $this->redirect('/admin/redes-sociais');
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('social.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->links->find($id)) {
            $this->abort(404);
        }
        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/redes-sociais');
            return;
        }

        $this->links->update($id, $data);
        AuditService::log('update', 'social', (string) $id, "Editou rede social: {$data['platform']}");
        Session::flash('success', 'Rede social atualizada.');
        $this->redirect('/admin/redes-sociais');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('social.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->links->find($id)) {
            $this->abort(404);
        }
        $this->links->delete($id);
        AuditService::log('delete', 'social', (string) $id, 'Removeu rede social');
        Session::flash('success', 'Rede social removida.');
        $this->redirect('/admin/redes-sociais');
    }

    public function reorder(Request $request): void
    {
        $this->authorize('social.edit');
        $this->verifyCsrf($request);

        $order = (array) $request->post('order', []);
        $pos = 1;
        $this->links->transaction(function () use ($order, &$pos) {
            foreach ($order as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $this->links->update($id, ['sort_order' => $pos++]);
                }
            }
        });
        $this->json(['ok' => true]);
    }

    private function collect(Request $request): array
    {
        $platform = trim((string) $request->post('platform', ''));
        $label = trim((string) $request->post('label', ''));
        if ($label === '' && isset(self::PLATFORMS[$platform])) {
            $label = self::PLATFORMS[$platform];
        }
        return [
            'platform'   => $platform,
            'label'      => $label,
            'url'        => trim((string) $request->post('url', '')),
            'icon'       => $platform,
            'is_active'  => $request->post('is_active') ? 1 : 0,
            'sort_order' => (int) $request->post('sort_order', 0),
        ];
    }

    private function validate(array $data): array
    {
        $v = new Validator($data, [
            'platform' => 'required|string|max:40',
            'label'    => 'required|string|max:80',
            'url'      => 'required|url|max:255',
        ], ['platform' => 'plataforma', 'label' => 'rótulo', 'url' => 'URL']);
        return $v->errors();
    }
}
