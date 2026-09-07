<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\HomeSection;
use App\Services\AuditService;
use App\Services\HtmlSanitizer;
use App\Services\UploadService;
use App\Validators\Validator;

/**
 * Gerenciamento das seções editáveis da Home.
 */
class HomeSectionController extends Controller
{
    private HomeSection $sections;

    public function __construct()
    {
        $this->sections = new HomeSection();
    }

    public function index(Request $request): void
    {
        $this->authorize('home.view');
        $this->viewAdmin('admin.home.index', [
            'title'       => 'Seções da Home',
            'breadcrumbs' => [['label' => 'Home']],
            'items'       => $this->sections->allOrdered(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('home.edit');
        $this->viewAdmin('admin.home.form', [
            'title'       => 'Nova seção',
            'breadcrumbs' => [['label' => 'Home', 'url' => '/admin/home'], ['label' => 'Nova']],
            'section'     => null,
            'errors'      => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('home.edit');
        $this->verifyCsrf($request);

        $data = $this->collect($request, null);
        $errors = $this->validate($data);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/home/criar');
            return;
        }

        $id = $this->sections->create($data);
        AuditService::log('create', 'home', (string) $id, "Criou a seção da home: {$data['key']}");
        Session::flash('success', 'Seção criada com sucesso.');
        $this->redirect('/admin/home');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('home.edit');
        $section = $this->sections->find((int) $params['id']);
        if (!$section) {
            $this->abort(404);
        }
        $this->viewAdmin('admin.home.form', [
            'title'       => 'Editar seção',
            'breadcrumbs' => [['label' => 'Home', 'url' => '/admin/home'], ['label' => 'Editar']],
            'section'     => $section,
            'errors'      => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('home.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $section = $this->sections->find($id);
        if (!$section) {
            $this->abort(404);
        }

        $data = $this->collect($request, $section);
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/home/{$id}/editar");
            return;
        }

        $this->sections->update($id, $data);
        AuditService::log('update', 'home', (string) $id, "Editou a seção da home: {$data['key']}");
        Session::flash('success', 'Seção atualizada.');
        $this->redirect('/admin/home');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('home.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $section = $this->sections->find($id);
        if (!$section) {
            $this->abort(404);
        }

        if (!empty($section['image'])) {
            (new UploadService())->delete($section['image']);
        }
        $this->sections->delete($id);
        AuditService::log('delete', 'home', (string) $id, "Excluiu a seção da home: {$section['key']}");
        Session::flash('success', 'Seção excluída.');
        $this->redirect('/admin/home');
    }

    /**
     * Endpoint AJAX para reordenar seções (protegido por CSRF).
     */
    public function reorder(Request $request): void
    {
        $this->authorize('home.edit');
        $this->verifyCsrf($request);

        $order = $request->post('order', []);
        if (!is_array($order)) {
            $this->json(['ok' => false], 422);
            return;
        }

        $this->sections->reorder($order);
        AuditService::log('reorder', 'home', null, 'Reordenou seções da home');
        $this->json(['ok' => true]);
    }

    private function collect(Request $request, ?array $existing): array
    {
        $key = trim((string) $request->post('key', ''));
        $key = $key !== '' ? str_slug($key) : ($existing['key'] ?? 'secao');

        // Upload de imagem opcional
        $image = $existing['image'] ?? null;
        $file = $request->file('image');
        if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploader = new UploadService();
            $newPath = $uploader->image($file, 'home');
            if ($newPath !== null) {
                if (!empty($existing['image'])) {
                    $uploader->delete($existing['image']);
                }
                $image = $newPath;
            }
        }

        return [
            'key'          => $key,
            'type'         => in_array($request->post('type'), ['content', 'features', 'screenshots', 'trailer', 'news', 'faq', 'cta'], true) ? $request->post('type') : 'content',
            'title'        => trim((string) $request->post('title', '')),
            'subtitle'     => trim((string) $request->post('subtitle', '')),
            'content'      => HtmlSanitizer::clean((string) $request->post('content', '')),
            'image'        => $image,
            'button_label' => trim((string) $request->post('button_label', '')),
            'button_url'   => trim((string) $request->post('button_url', '')),
            'is_active'    => $request->post('is_active') ? 1 : 0,
            'sort_order'   => (int) $request->post('sort_order', 0),
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'key'   => 'required|slug|max:60',
            'type'  => 'required|string',
            'title' => 'max:200',
        ], ['key' => 'chave', 'type' => 'tipo', 'title' => 'título']);
        $errors = $v->errors();
        if (!isset($errors['key']) && $this->sections->keyExists($data['key'], $ignoreId)) {
            $errors['key'] = 'Já existe uma seção com esta chave.';
        }
        return $errors;
    }
}
