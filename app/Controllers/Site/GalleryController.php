<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\GalleryAlbum;
use App\Models\GalleryItem;
use App\Services\SeoService;

/**
 * Galeria pública: lista álbuns e exibe itens de um álbum.
 */
class GalleryController extends Controller
{
    public function index(Request $request): void
    {
        $this->viewSite('site.gallery.index', [
            'seo'    => SeoService::build(['title' => 'Galeria']),
            'albums' => (new GalleryAlbum())->activeWithCover(),
        ]);
    }

    public function album(Request $request, array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $albumModel = new GalleryAlbum();
        $album = $albumModel->findActiveBySlug($slug);

        if (!$album) {
            $this->abort(404);
        }

        $this->viewSite('site.gallery.album', [
            'seo'   => SeoService::build([
                'title'       => $album['title'],
                'description' => $album['description'],
                'image'       => $album['cover_image'],
            ]),
            'album' => $album,
            'items' => (new GalleryItem())->activeByAlbum((int) $album['id']),
        ]);
    }
}
