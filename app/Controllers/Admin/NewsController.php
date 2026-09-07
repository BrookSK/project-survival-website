<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\News;
use App\Models\NewsCategory;
use App\Services\AuditService;
use App\Services\HtmlSanitizer;
use App\Services\NotificationService;
use App\Services\UploadService;
use App\Validators\Validator;

/**
 * CRUD de notícias (CMS completo):
 * agendamento, destaque com limite, soft delete, bulk actions, sanitização,
 * transações e ordenação/filtros validados no backend.
 */
class NewsController extends Controller
{
    private News $news;
    private NewsCategory $categories;

    public function __construct()
    {
        $this->news = new News();
        $this->categories = new NewsCategory();
    }

    public function index(Request $request): void
    {
        $this->authorize('news.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) setting('items_per_page', 15);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'published_at');
        $dir = (string) $request->query('dir', 'desc');

        $result = $this->news->paginate($page, $perPage, $search, $status ?: null, $sort, $dir);

        $this->viewAdmin('admin.news.index', [
            'title'       => 'Notícias',
            'breadcrumbs' => [['label' => 'Notícias']],
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'search'      => $search,
            'status'      => $status,
            'sort'        => $sort,
            'dir'         => $dir,
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('news.create');
        $this->viewAdmin('admin.news.form', [
            'title'         => 'Nova notícia',
            'breadcrumbs'   => [['label' => 'Notícias', 'url' => '/admin/noticias'], ['label' => 'Nova']],
            'news_item'     => null,
            'allCategories' => $this->categories->all('name'),
            'selectedCats'  => [],
            'featuredLimit' => (int) setting('featured_news_limit', 3),
            'featuredCount' => $this->news->featuredCount(),
            'errors'        => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('news.create');
        $this->verifyCsrf($request);

        $data = $this->collect($request);
        $errors = $this->validate($data);

        $uploader = new UploadService();
        $imagePath = null;
        $file = $request->file('featured_image');
        if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = $uploader->image($file, 'news');
            if ($imagePath === null) {
                $errors['featured_image'] = implode(' ', $uploader->errors());
            }
        }

        // Regra de destaque: respeita o limite configurável
        $isFeatured = $this->resolveFeatured($data, null, $errors);

        if ($errors) {
            $this->redirectWithErrors($errors, $data, '/admin/noticias/criar');
            return;
        }

        $publishedAt = $this->resolvePublishedAt($data, null);

        try {
            $id = $this->news->transaction(function () use ($data, $imagePath, $isFeatured, $publishedAt) {
                $newId = $this->news->create([
                    'title'           => $data['title'],
                    'slug'            => $data['slug'],
                    'excerpt'         => $data['excerpt'],
                    'content'         => $data['content'],
                    'featured_image'  => $imagePath,
                    'status'          => $data['status'],
                    'is_featured'     => $isFeatured,
                    'author_id'       => auth_user()['id'],
                    'seo_title'       => $data['seo_title'],
                    'seo_description' => $data['seo_description'],
                    'published_at'    => $publishedAt,
                ]);
                $this->news->syncCategories($newId, $data['categories']);
                return $newId;
            });
        } catch (\Throwable $e) {
            $uploader->delete($imagePath);
            \App\Core\Logger::exception($e);
            Session::flash('error', 'Não foi possível criar a notícia.');
            $this->redirect('/admin/noticias/criar');
            return;
        }

        AuditService::log('create', 'news', (string) $id, "Criou a notícia: {$data['title']}");
        if ($data['status'] === 'published') {
            NotificationService::push('success', 'Notícia publicada', $data['title'], '/admin/noticias/' . $id . '/editar');
        }

        Session::flash('success', 'Notícia criada com sucesso.');
        $this->redirect('/admin/noticias');
    }

    public function edit(Request $request, array $params): void
    {
        $this->authorize('news.edit');
        $item = $this->news->find((int) $params['id']);
        if (!$item) {
            $this->abort(404);
        }

        $this->viewAdmin('admin.news.form', [
            'title'         => 'Editar notícia',
            'breadcrumbs'   => [['label' => 'Notícias', 'url' => '/admin/noticias'], ['label' => 'Editar']],
            'news_item'     => $item,
            'allCategories' => $this->categories->all('name'),
            'selectedCats'  => $this->news->categoryIds((int) $item['id']),
            'featuredLimit' => (int) setting('featured_news_limit', 3),
            'featuredCount' => $this->news->featuredCount((int) $item['id']),
            'errors'        => errors(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->authorize('news.edit');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->news->find($id);
        if (!$item) {
            $this->abort(404);
        }

        $data = $this->collect($request);
        $errors = $this->validate($data, $id);

        $uploader = new UploadService();
        $imagePath = $item['featured_image'];
        $file = $request->file('featured_image');
        if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $newPath = $uploader->image($file, 'news');
            if ($newPath === null) {
                $errors['featured_image'] = implode(' ', $uploader->errors());
            } else {
                $uploader->delete($item['featured_image']);
                $imagePath = $newPath;
            }
        }

        $isFeatured = $this->resolveFeatured($data, $id, $errors);

        if ($errors) {
            $this->redirectWithErrors($errors, $data, "/admin/noticias/{$id}/editar");
            return;
        }

        $publishedAt = $this->resolvePublishedAt($data, $item);

        $this->news->transaction(function () use ($id, $data, $imagePath, $isFeatured, $publishedAt) {
            $this->news->update($id, [
                'title'           => $data['title'],
                'slug'            => $data['slug'],
                'excerpt'         => $data['excerpt'],
                'content'         => $data['content'],
                'featured_image'  => $imagePath,
                'status'          => $data['status'],
                'is_featured'     => $isFeatured,
                'seo_title'       => $data['seo_title'],
                'seo_description' => $data['seo_description'],
                'published_at'    => $publishedAt,
            ]);
            $this->news->syncCategories($id, $data['categories']);
        });

        AuditService::log('update', 'news', (string) $id, "Editou a notícia: {$data['title']}");
        Session::flash('success', 'Notícia atualizada com sucesso.');
        $this->redirect('/admin/noticias');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->authorize('news.delete');
        $this->verifyCsrf($request);

        $id = (int) $params['id'];
        $item = $this->news->find($id);
        if (!$item) {
            $this->abort(404);
        }

        // Soft delete (mantém a imagem para eventual restauração)
        $this->news->delete($id);
        AuditService::log('delete', 'news', (string) $id, "Excluiu a notícia: {$item['title']}");

        Session::flash('success', 'Notícia movida para a lixeira.');
        $this->redirect('/admin/noticias');
    }

    /**
     * Ações em massa: publicar, arquivar ou excluir várias notícias.
     */
    public function bulk(Request $request): void
    {
        $this->authorize('news.edit');
        $this->verifyCsrf($request);

        $action = (string) $request->post('bulk_action', '');
        $ids = array_map('intval', (array) $request->post('ids', []));
        $ids = array_filter($ids, fn($v) => $v > 0);

        if (!$ids || !in_array($action, ['publish', 'archive', 'delete'], true)) {
            Session::flash('error', 'Selecione itens e uma ação válida.');
            $this->redirect('/admin/noticias');
            return;
        }

        if ($action === 'delete') {
            $this->authorize('news.delete');
        }

        $this->news->transaction(function () use ($action, $ids) {
            foreach ($ids as $id) {
                if ($action === 'publish') {
                    $this->news->update($id, ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);
                } elseif ($action === 'archive') {
                    $this->news->update($id, ['status' => 'archived']);
                } elseif ($action === 'delete') {
                    $this->news->delete($id);
                }
            }
        });

        AuditService::log('bulk', 'news', implode(',', $ids), "Ação em massa ({$action}) em " . count($ids) . ' notícia(s)');
        Session::flash('success', count($ids) . ' notícia(s) processada(s).');
        $this->redirect('/admin/noticias');
    }

    private function collect(Request $request): array
    {
        $title = trim((string) $request->post('title', ''));
        $slug = trim((string) $request->post('slug', ''));
        $slug = $slug !== '' ? str_slug($slug) : str_slug($title);
        $cats = $request->post('categories', []);

        return [
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => trim((string) $request->post('excerpt', '')),
            'content'         => HtmlSanitizer::clean((string) $request->post('content', '')),
            'status'          => in_array($request->post('status'), ['draft', 'scheduled', 'published', 'archived'], true) ? $request->post('status') : 'draft',
            'is_featured'     => $request->post('is_featured') ? 1 : 0,
            'scheduled_at'    => trim((string) $request->post('scheduled_at', '')),
            'seo_title'       => trim((string) $request->post('seo_title', '')),
            'seo_description' => trim((string) $request->post('seo_description', '')),
            'categories'      => is_array($cats) ? $cats : [],
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $v = new Validator($data, [
            'title'   => 'required|string|min:2|max:200',
            'slug'    => 'required|slug|max:200',
            'excerpt' => 'max:500',
            'status'  => 'required|in:draft,scheduled,published,archived',
        ], ['title' => 'título', 'slug' => 'slug']);

        $errors = $v->errors();
        if (!isset($errors['slug']) && $this->news->slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'Já existe uma notícia com este slug.';
        }
        if ($data['status'] === 'scheduled' && $data['scheduled_at'] === '') {
            $errors['scheduled_at'] = 'Informe a data de publicação agendada.';
        }
        return $errors;
    }

    /**
     * Aplica a regra de destaque respeitando o limite configurável.
     * Adiciona erro se o limite for excedido.
     */
    private function resolveFeatured(array $data, ?int $currentId, array &$errors): int
    {
        if (empty($data['is_featured'])) {
            return 0;
        }
        $limit = (int) setting('featured_news_limit', 3);
        $count = $this->news->featuredCount($currentId);
        if ($count >= $limit) {
            $errors['is_featured'] = "Limite de {$limit} notícias em destaque atingido. Remova outra antes.";
            return 0;
        }
        return 1;
    }

    /**
     * Resolve published_at conforme o status:
     * - scheduled: usa a data informada (futura).
     * - published: agora, se ainda não tinha data.
     * - draft/archived: mantém o que existia.
     */
    private function resolvePublishedAt(array $data, ?array $existing): ?string
    {
        $existingDate = $existing['published_at'] ?? null;

        if ($data['status'] === 'scheduled' && $data['scheduled_at'] !== '') {
            $ts = strtotime(str_replace('T', ' ', $data['scheduled_at']));
            return $ts ? date('Y-m-d H:i:s', $ts) : $existingDate;
        }
        if ($data['status'] === 'published') {
            return $existingDate ?: date('Y-m-d H:i:s');
        }
        return $existingDate;
    }
}
