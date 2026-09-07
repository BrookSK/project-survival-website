<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\GalleryAlbum;
use App\Models\GalleryItem;
use App\Services\AuditService;
use App\Services\UploadService;
use App\Validators\Validator;

/**
 * CRUD de álbuns e itens da galeria.
 */
class GalleryController extends Controller
{
    private GalleryAlbum $albums;
    private GalleryItem $items;

    public function __construct()
    {
        $this->albums = new GalleryAlbum();
        $this->items = new GalleryItem();
    }

    public function index(Request $request): void
    {
        $this->authorize('gallery.view');
        $this->viewAdmin('admin.gallery.index', [
            'title'       => 'Galeria',
            'breadcrumbs' => [['label' => 'Galeria']],
            'items'       => $this->albums->allWithCounts(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('gallery.create');
        $this->viewAdmin('admin.gallery.form', [
            'title'       => 'Novo álbum',
            'breadcrumbs' => [['label' => 'Galeria', 'url' => '/admin/galeria'], ['label' => 'Novo álbum']],
            'album'       => null,
            'albumItems'  => [],
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('gallery.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/galeria/criar');
            return;
        }

        $id = $this->albums->create($data);
        AuditService::log('create', 'gallery', (string) $id, "Criou o álbum: {$data['title']}");
        Session::flash('success', 'Álbum criado. Agora adicione imagens.');
        $this->redirect("/admin/galeria/{$id}/editar");
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('gallery.edit');
        $album = $this->albums->find((int) $params['id']);
        if (!$album) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.gallery.form', [
            'title'       => 'Editar álbum',
            'breadcrumbs' => [['label' => 'Galeria', 'url' => '/admin/galeria'], ['label' => 'Editar']],
            'album'       => $album,
            'albumItems'  => $this->items->byAlbum((int) $album['id']),
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('gallery.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        if (!$this->albums->find($id)) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/galeria/{$id}/editar");
            return;
        }

        $this->albums->update($id, $data);
        AuditService::log('update', 'gallery', (string) $id, "Editou o álbum: {$data['title']}");
        Session::flash('success', 'Álbum atualizado.');
        $this->redirect("/admin/galeria/{$id}/editar");
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('gallery.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $album = $this->albums->find($id);
        if (!$album) {
            $this->abort(404);
        }

        // Remove arquivos dos itens antes de apagar o álbum
        $uploader = new UploadService();
        foreach ($this->items->byAlbum($id) as $item) {
            $uploader->delete($item['file_path']);
        }
        $uploader->delete($album['cover_image']);

        $this->albums->delete($id);
        AuditService::log('delete', 'gallery', (string) $id, "Excluiu o álbum: {$album['title']}");
        Session::flash('success', 'Álbum excluído.');
        $this->redirect('/admin/galeria');
    }

    /**
     * Adiciona um item (imagem ou vídeo) a um álbum.
     */
    public function addItem(Request $request, array $params): void
    {
        $this->authorize('gallery.edit');
        $this->verifyCsrf($request);

        $albumId = (int) $params['id'];
        if (!$this->albums->find($albumId)) {
            $this->abort(404);
        }

        $type = $request->post('type') === 'video' ? 'video' : 'image';
        $itemData = [
            'album_id'   => $albumId,
            'type'       => $type,
            'title'      => trim((string) $request->post('item_title', '')),
            'alt_text'   => trim((string) $request->post('alt_text', '')),
            'is_active'  => 1,
            'sort_order' => (int) $request->post('sort_order', 0),
        ];

        if ($type === 'video') {
            $videoUrl = trim((string) $request->post('video_url', ''));
            $v = new Validator(['video_url' => $videoUrl], ['video_url' => 'required|url'], ['video_url' => 'URL do vídeo']);
            if ($v->fails()) {
                $this->redirectWithErrors($v->errors(), [], "/admin/galeria/{$albumId}/editar");
                return;
            }
            $itemData['video_url'] = $videoUrl;
        } else {
            $file = $request->file('image');
            $uploader = new UploadService();
            $path = $file ? $uploader->image($file, 'gallery') : null;
            if ($path === null) {
                $this->redirectWithErrors(['image' => implode(' ', $uploader->errors()) ?: 'Envie uma imagem válida.'], [], "/admin/galeria/{$albumId}/editar");
                return;
            }
            $itemData['file_path'] = $path;
        }

        $this->items->create($itemData);
        AuditService::log('create', 'gallery_item', (string) $albumId, 'Adicionou item ao álbum');
        Session::flash('success', 'Item adicionado ao álbum.');
        $this->redirect("/admin/galeria/{$albumId}/editar");
    }

    public function destroyItem(Request $request, array $params): void
    {
        $this->authorize('gallery.edit');
        $this->verifyCsrf($request);

        $itemId = (int) $params['id'];
        $item = $this->items->find($itemId);
        if (!$item) {
            $this->abort(404);
        }

        (new UploadService())->delete($item['file_path']);
        $this->items->delete($itemId);
        AuditService::log('delete', 'gallery_item', (string) $itemId, 'Removeu item do álbum');
        Session::flash('success', 'Item removido.');
        $this->redirect('/admin/galeria/' . (int) $item['album_id'] . '/editar');
    }

    /**
     * Reordena os itens de um álbum (endpoint AJAX, protegido por CSRF).
     */
    public function reorderItems(Request $request): void
    {
        $this->authorize('gallery.edit');
        $this->verifyCsrf($request);

        $order = (array) $request->post('order', []);
        $pos = 1;
        $this->items->transaction(function () use ($order, &$pos) {
            foreach ($order as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $this->items->update($id, ['sort_order' => $pos++]);
                }
            }
        });
        AuditService::log('reorder', 'gallery_item', null, 'Reordenou itens da galeria');
        $this->json(['ok' => true]);
    }

    private function collect(Request $request): array
    {
        $title = trim((string) $request->post('title', ''));
        $slug = trim((string) $request->post('slug', ''));
        return [
            'title'       => $title,
            'slug'        => $slug !== '' ? str_slug($slug) : str_slug($title),
            'description' => trim((string) $request->post('description', '')),
            'is_active'   => $request->post('is_active') ? 1 : 0,
            'sort_order'  => (int) $request->post('sort_order', 0),
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'title' => 'required|string|min:2|max:150',
            'slug'  => 'required|slug|max:150',
        ], ['title' => 'título', 'slug' => 'slug']);
        $errors = $v->errors();
        if (!isset($errors['slug']) && $this->albums->slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'Já existe um álbum com este slug.';
        }
        return $errors;
    }
}
