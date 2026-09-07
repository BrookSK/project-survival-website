<?php

namespace App\Services;

/**
 * Serviço de SEO: monta os metadados de cada página.
 *
 * Combina valores específicos da página com os padrões configurados em
 * Settings (grupo 'seo'). O layout do site consome o array resultante para
 * renderizar title, meta description, canonical, Open Graph e Twitter Cards.
 */
class SeoService
{
    /**
     * Constrói o conjunto de metadados para uma página.
     *
     * @param array $data Campos opcionais: title, description, keywords, image,
     *                    canonical, robots, type (website|article).
     */
    public static function build(array $data = []): array
    {
        $siteName = SettingsService::get('site_name', 'Site');
        $defaults = SettingsService::group('seo');

        $title = $data['title'] ?? '';
        $fullTitle = $title !== ''
            ? $title . ' | ' . $siteName
            : ($defaults['seo_default_title'] ?? $siteName);

        $description = $data['description'] ?? ($defaults['seo_default_description'] ?? '');
        $keywords = $data['keywords'] ?? ($defaults['seo_default_keywords'] ?? '');
        $image = $data['image'] ?? ($defaults['seo_og_image'] ?? '');

        // Resolve imagem para URL absoluta quando for caminho de upload
        if ($image !== '' && !preg_match('#^https?://#', $image)) {
            $image = uploaded($image);
        }

        return [
            'title'       => trim($fullTitle),
            'description' => self::truncate((string) $description, 300),
            'keywords'    => $keywords,
            'image'       => $image,
            'canonical'   => $data['canonical'] ?? self::currentUrl(),
            'robots'      => $data['robots'] ?? 'index,follow',
            'type'        => $data['type'] ?? 'website',
            'site_name'   => $siteName,
            'twitter'     => $defaults['seo_twitter_handle'] ?? '',
        ];
    }

    private static function currentUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return url(ltrim($path, '/'));
    }

    private static function truncate(string $text, int $len): string
    {
        $text = trim(strip_tags($text));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
        }
        return strlen($text) > $len ? substr($text, 0, $len - 1) . '…' : $text;
    }
}
