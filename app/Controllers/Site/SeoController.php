<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

/**
 * Recursos técnicos de SEO: sitemap.xml e robots.txt gerados dinamicamente.
 */
class SeoController extends Controller
{
    public function sitemap(Request $request): void
    {
        // O sitemap muda pouco entre requisições; cache de 1h evita varrer
        // todas as tabelas a cada acesso de robô. Regenera automaticamente.
        $xml = \App\Services\CacheService::remember('sitemap.xml', 3600, function () {
            return $this->buildSitemap();
        });

        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml;
    }

    /**
     * Monta o XML do sitemap a partir das entidades publicadas.
     */
    private function buildSitemap(): string
    {
        $db = Database::getInstance();
        $urls = [];

        // Páginas fixas principais
        foreach (['', 'noticias', 'galeria', 'faq', 'contato'] as $path) {
            $urls[] = ['loc' => url($path), 'priority' => $path === '' ? '1.0' : '0.7'];
        }

        // Páginas publicadas
        try {
            $pages = $db->fetchAll("SELECT slug, updated_at FROM pages WHERE status = 'published'");
            foreach ($pages as $p) {
                $urls[] = ['loc' => url($p['slug']), 'lastmod' => $p['updated_at'], 'priority' => '0.6'];
            }
        } catch (\Throwable $e) { /* ignora */ }

        // Notícias publicadas
        try {
            $news = $db->fetchAll(
                "SELECT slug, updated_at FROM news WHERE status = 'published' AND published_at <= NOW()"
            );
            foreach ($news as $n) {
                $urls[] = ['loc' => url('noticias/' . $n['slug']), 'lastmod' => $n['updated_at'], 'priority' => '0.8'];
            }
        } catch (\Throwable $e) { /* ignora */ }

        // Álbuns ativos
        try {
            $albums = $db->fetchAll("SELECT slug, updated_at FROM gallery_albums WHERE is_active = 1");
            foreach ($albums as $a) {
                $urls[] = ['loc' => url('galeria/' . $a['slug']), 'lastmod' => $a['updated_at'], 'priority' => '0.5'];
            }
        } catch (\Throwable $e) { /* ignora */ }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= "    <lastmod>" . date('Y-m-d', strtotime($u['lastmod'])) . "</lastmod>\n";
            }
            $xml .= "    <priority>{$u['priority']}</priority>\n  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    public function robots(Request $request): void
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /install',
            '',
            'Sitemap: ' . url('sitemap.xml'),
        ];

        header('Content-Type: text/plain; charset=UTF-8');
        echo implode("\n", $lines);
    }
}
