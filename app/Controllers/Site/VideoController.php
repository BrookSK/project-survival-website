<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Video;
use App\Services\SeoService;

/**
 * Página pública de vídeos/trailers.
 */
class VideoController extends Controller
{
    public function index(Request $request): void
    {
        $this->viewSite('site.videos', [
            'seo'    => SeoService::build(['title' => 'Vídeos']),
            'videos' => (new Video())->activeOrdered(),
        ]);
    }
}
