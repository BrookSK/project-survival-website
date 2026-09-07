<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Video;
use App\Services\AuditService;
use App\Validators\Validator;

/**
 * CRUD de vídeos/trailers (YouTube, Vimeo ou URL externa).
 */
class VideoController extends Controller
{
    private Video $videos;

    public function __construct()
    {
        $this->videos = new Video();
    }

    public function index(Request $request): void
    {
        $this->authorize('videos.view');
        $this->viewAdmin('admin.videos.index', [
            'title'       => 'Vídeos',
            'breadcrumbs' => [['label' => 'Vídeos']],
            'items'       => $this->videos->allOrdered(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('videos.create');
        $this->viewAdmin('admin.videos.form', [
            'title'       => 'Novo vídeo',
            'breadcrumbs' => [['label' => 'Vídeos', 'url' => '/admin/videos'], ['label' => 'Novo']],
            'video'       => null,
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('videos.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/videos/criar');
            return;
        }

        $id = $this->videos->create($data);
        AuditService::log('create', 'videos', (string) $id, "Criou o vídeo: {$data['title']}");
        Session::flash('success', 'Vídeo criado com sucesso.');
        $this->redirect('/admin/videos');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('videos.edit');
        $video = $this->videos->find((int) $params['id']);
        if (!$video) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.videos.form', [
            'title'       => 'Editar vídeo',
            'breadcrumbs' => [['label' => 'Vídeos', 'url' => '/admin/videos'], ['label' => 'Editar']],
            'video'       => $video,
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('videos.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->videos->find($id)) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/videos/{$id}/editar");
            return;
        }

        $this->videos->update($id, $data);
        AuditService::log('update', 'videos', (string) $id, "Editou o vídeo: {$data['title']}");
        Session::flash('success', 'Vídeo atualizado.');
        $this->redirect('/admin/videos');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('videos.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $video = $this->videos->find($id);
        if (!$video) {
            $this->abort(404);
        }
        $this->videos->delete($id);
        AuditService::log('delete', 'videos', (string) $id, "Excluiu o vídeo: {$video['title']}");
        Session::flash('success', 'Vídeo excluído.');
        $this->redirect('/admin/videos');
    }

    public function reorder(Request $request): void
    {
        $this->authorize('videos.edit');
        $this->verifyCsrf($request);

        $order = (array) $request->post('order', []);
        $pos = 1;
        $this->videos->transaction(function () use ($order, &$pos) {
            foreach ($order as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $this->videos->update($id, ['sort_order' => $pos++]);
                }
            }
        });
        $this->json(['ok' => true]);
    }

    private function collect(Request $request): array
    {
        $url = trim((string) $request->post('url', ''));
        $parsed = Video::parseUrl($url);

        // Só substitui thumbnail automática se o admin não informou uma
        $thumb = trim((string) $request->post('thumbnail', ''));
        if ($thumb === '' && $parsed['thumbnail']) {
            $thumb = $parsed['thumbnail'];
        }

        return [
            'title'       => trim((string) $request->post('title', '')),
            'provider'    => $parsed['provider'],
            'url'         => $url,
            'video_id'    => $parsed['video_id'],
            'thumbnail'   => $thumb ?: null,
            'description' => trim((string) $request->post('description', '')),
            'is_featured' => $request->post('is_featured') ? 1 : 0,
            'is_active'   => $request->post('is_active') ? 1 : 0,
            'sort_order'  => (int) $request->post('sort_order', 0),
        ];
    }

    private function validate(array $data): array
    {
        $v = new Validator($data, [
            'title' => 'required|string|min:2|max:200',
            'url'   => 'required|url|max:500',
        ], ['title' => 'título', 'url' => 'URL do vídeo']);
        return $v->errors();
    }
}
