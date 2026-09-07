<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Faq;
use App\Services\SeoService;

/**
 * Página pública de perguntas frequentes.
 */
class FaqController extends Controller
{
    public function index(Request $request): void
    {
        $this->viewSite('site.faq', [
            'seo'    => SeoService::build(['title' => 'Perguntas Frequentes']),
            'groups' => (new Faq())->activeGrouped(),
        ]);
    }
}
