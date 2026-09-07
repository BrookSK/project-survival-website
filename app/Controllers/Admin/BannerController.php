<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Banner;
use App\Services\AuditService;
use App\Services\UploadService;
use App\Validators\Validator;

/**
 * CRUD de banners/slides.
 */
class BannerController extends Controller
{
    private Banner $banners;

    public function __construct()
    {
        $this->banners = new Banner();
    }

    public function index(Request $request): void
    {
        $this->authorize('banners.view');
        $this->viewAdmin('admin.banners.index', [
            'title'       => 'Banners',
            'breadcrumbs' => [['label' => 'Banners']],
            'items'       => $this->banners->allOrdered(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('banners.create');
        $this->viewAdmin('admin.banners.form', [
            'title'       => 'Novo banner',
            'breadcrumbs' => [['label' => 'Banners', 'url' => '/admin/banners'], ['label' => 'Novo']],
            'banner'      => null,
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('banners.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);

        $uploader = new UploadService();
        $imagePath = null;
        $file = $request->file('image');
        if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = $uploader->image($file, 'banners');
            if ($imagePath === null) {
                $errors['image'] = implode(' ', $uploader->errors());
            }
        }

        $imageMobile = null;
        $fileM = $request->file('image_mobile');
        if ($fileM && isset($fileM['error']) && $fileM['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageMobile = $uploader->image($fileM, 'banners');
            if ($imageMobile === null) {
                $errors['image_mobile'] = implode(' ', $uploader->errors());
            }
        }

        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/banners/criar');
            return;
        }

        $data['image_path'] = $imagePath;
        $data['image_mobile'] = $imageMobile;
        $id = $this->banners->create($data);
        AuditService::log('create', 'banners', (string) $id, "Criou o banner: {$data['title']}");
        Session::flash('success', 'Banner criado com sucesso.');
        $this->redirect('/admin/banners');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('banners.edit');
        $banner = $this->banners->find((int) $params['id']);
        if (!$banner) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.banners.form', [
            'title'       => 'Editar banner',
            'breadcrumbs' => [['label' => 'Banners', 'url' => '/admin/banners'], ['label' => 'Editar']],
            'banner'      => $banner,
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('banners.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $banner = $this->banners->find($id);
        if (!$banner) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data);

        $uploader = new UploadService();
        $imagePath = $banner['image_path'];
        $file = $request->file('image');
        if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $newPath = $uploader->image($file, 'banners');
            if ($newPath === null) {
                $errors['image'] = implode(' ', $uploader->errors());
            } else {
                $uploader->delete($banner['image_path']);
                $imagePath = $newPath;
            }
        }

        $imageMobile = $banner['image_mobile'] ?? null;
        $fileM = $request->file('image_mobile');
        if ($fileM && isset($fileM['error']) && $fileM['error'] !== UPLOAD_ERR_NO_FILE) {
            $newMobile = $uploader->image($fileM, 'banners');
            if ($newMobile === null) {
                $errors['image_mobile'] = implode(' ', $uploader->errors());
            } else {
                $uploader->delete($banner['image_mobile'] ?? null);
                $imageMobile = $newMobile;
            }
        }

        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/banners/{$id}/editar");
            return;
        }

        $data['image_path'] = $imagePath;
        $data['image_mobile'] = $imageMobile;
        $this->banners->update($id, $data);
        AuditService::log('update', 'banners', (string) $id, "Editou o banner: {$data['title']}");
        Session::flash('success', 'Banner atualizado.');
        $this->redirect('/admin/banners');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('banners.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $banner = $this->banners->find($id);
        if (!$banner) {
            $this->abort(404);
        }

        $uploader = new UploadService();
        $uploader->delete($banner['image_path']);
        $uploader->delete($banner['image_mobile'] ?? null);
        $this->banners->delete($id);
        AuditService::log('delete', 'banners', (string) $id, "Excluiu o banner: {$banner['title']}");
        Session::flash('success', 'Banner excluído.');
        $this->redirect('/admin/banners');
    }

    private function collect(Request $request): array
    {
        return [
            'title'      => trim((string) $request->post('title', '')),
            'subtitle'   => trim((string) $request->post('subtitle', '')),
            'link_url'   => trim((string) $request->post('link_url', '')),
            'link_label' => trim((string) $request->post('link_label', '')),
            'position'   => trim((string) $request->post('position', 'home_hero')) ?: 'home_hero',
            'is_active'  => $request->post('is_active') ? 1 : 0,
            'sort_order' => (int) $request->post('sort_order', 0),
            'starts_at'  => $request->post('starts_at') ?: null,
            'ends_at'    => $request->post('ends_at') ?: null,
        ];
    }

    private function validate(array $data): array
    {
        $rules = ['position' => 'required|string|max:60'];
        if (!empty($data['link_url'])) {
            $rules['link_url'] = 'url';
        }
        $v = new Validator($data, $rules, ['position' => 'posição', 'link_url' => 'URL do link']);
        return $v->errors();
    }
}
