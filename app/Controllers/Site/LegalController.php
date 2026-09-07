<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Models\PrivacyPolicy;
use App\Services\SeoService;

/**
 * Páginas públicas dos documentos legais (Privacidade, Termos, Termos de
 * Compra, Reembolso, Cookies). Renderiza sempre a versão PUBLICADA vigente,
 * com número de versão e data de vigência. Se não houver versão publicada,
 * mostra um aviso neutro (sem inventar conteúdo).
 */
class LegalController extends Controller
{
    private PrivacyPolicy $policies;

    public function __construct()
    {
        $this->policies = new PrivacyPolicy();
    }

    /**
     * Resolve o slug a partir da URI (ex.: "/privacidade" -> "privacidade").
     */
    public function show(Request $request): void
    {
        $slug = trim($request->uri(), '/');
        $this->render($slug);
    }

    /**
     * Página de preferências de cookies. Explica as categorias reais e permite
     * gerenciar a escolha (via JavaScript, sem trackers de terceiros hoje).
     */
    public function cookiePreferences(Request $request): void
    {
        $doc = $this->policies->publishedBySlug('cookies');
        $this->viewSite('site.legal.cookies', [
            'seo'         => SeoService::build(['title' => 'Preferências de Cookies']),
            'title'       => 'Preferências de Cookies',
            'policyUrl'   => '/cookies',
            'hasPolicy'   => $doc !== null,
        ]);
    }

    private function render(string $slug): void
    {
        $doc = $this->policies->publishedBySlug($slug);
        $policy = $this->policies->findBySlug($slug);

        if (!$policy) {
            $this->abort(404);
        }

        $title = $policy['title'];
        $version = $doc['version']['version'] ?? null;
        $effectiveAt = $doc['version']['effective_at'] ?? null;
        $content = $doc['version']['content'] ?? null;

        $this->viewSite('site.legal.show', [
            'seo'         => SeoService::build(['title' => $title, 'type' => 'article']),
            'title'       => $title,
            'version'     => $version,
            'effectiveAt' => $effectiveAt,
            'content'     => $content,
            'contactEmail'=> (string) setting('privacy_contact_email', ''),
        ]);
    }
}
