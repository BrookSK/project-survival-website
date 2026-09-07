<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Media;
use App\Services\AuditService;
use App\Services\MediaService;

/**
 * Biblioteca de mídia: upload, listagem, edição de metadados e exclusão.
 */
class MediaController extends Controller
{
    private Media $media;

    public function __construct()
    {
        $this->media = new Media();
    }

    public function index(Request $request): void
    {
        $this->authorize('media.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 24;
        $search = trim((string) $request->query('q', ''));

        $result = $this->media->paginate($page, $perPage, $search);

        $this->viewAdmin('admin.media.index', [
            'title'        => 'Biblioteca de mídia',
            'breadcrumbs'  => [['label' => 'Mídia']],
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $page,
            'perPage'      => $perPage,
            'search'       => $search,
            'gdAvailable'  => MediaService::gdAvailable(),
            'webpSupported'=> MediaService::webpSupported(),
        ]);
    }

    /**
     * Upload de um ou mais arquivos. Responde JSON quando AJAX.
     */
    public function store(Request $request): void
    {
        $this->authorize('media.upload');
        $this->verifyCsrf($request);

        $files = $this->normalizeFiles($request->file('files') ?? $request->file('file'));
        $service = new MediaService();
        $created = [];
        $failed = [];

        foreach ($files as $file) {
            $id = $service->store($file, (int) (auth_user()['id'] ?? 0) ?: null);
            if ($id) {
                $created[] = $id;
                AuditService::log('upload', 'media', (string) $id, 'Enviou mídia: ' . ($file['name'] ?? ''));
            } else {
                $failed[] = ($file['name'] ?? 'arquivo') . ': ' . implode(' ', $service->errors());
            }
        }

        if ($request->wantsJson()) {
            $this->json([
                'success' => count($created),
                'failed'  => $failed,
            ]);
            return;
        }

        if ($created) {
            Session::flash('success', count($created) . ' arquivo(s) enviado(s).');
        }
        if ($failed) {
            Session::flash('error', 'Alguns arquivos falharam: ' . implode(' | ', $failed));
        }
        $this->redirect('/admin/midia');
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('media.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->media->find($id)) {
            $this->abort(404);
        }

        $this->media->updateMeta(
            $id,
            trim((string) $request->post('title', '')),
            trim((string) $request->post('alt_text', ''))
        );
        AuditService::log('update', 'media', (string) $id, 'Editou metadados de mídia');

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
            return;
        }
        Session::flash('success', 'Mídia atualizada.');
        $this->redirect('/admin/midia');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('media.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->media->find($id);
        if (!$item) {
            $this->abort(404);
        }

        (new MediaService())->remove($item);
        AuditService::log('delete', 'media', (string) $id, 'Excluiu mídia');

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
            return;
        }
        Session::flash('success', 'Mídia excluída.');
        $this->redirect('/admin/midia');
    }

    /**
     * Normaliza a estrutura de $_FILES para uma lista de arquivos individuais,
     * suportando tanto input único quanto múltiplo (name="files[]").
     */
    private function normalizeFiles(?array $input): array
    {
        if (!$input || !isset($input['name'])) {
            return [];
        }

        // Upload único
        if (!is_array($input['name'])) {
            return $input['error'] === UPLOAD_ERR_NO_FILE ? [] : [$input];
        }

        // Upload múltiplo
        $files = [];
        foreach ($input['name'] as $i => $name) {
            if (($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name'     => $name,
                'type'     => $input['type'][$i] ?? '',
                'tmp_name' => $input['tmp_name'][$i] ?? '',
                'error'    => $input['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $input['size'][$i] ?? 0,
            ];
        }
        return $files;
    }
}
