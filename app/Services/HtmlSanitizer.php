<?php

namespace App\Services;

/**
 * Sanitizador de HTML para conteúdo do editor (páginas, notícias, FAQ).
 *
 * Estratégia de whitelist rígida, sem dependências externas:
 *  - Usa DOMDocument quando disponível (limpeza estrutural robusta).
 *  - Fallback para strip_tags quando ext-dom não está presente.
 *
 * Remove: <script>, <style>, <iframe> não permitido, atributos on*,
 * URLs javascript:/data: perigosas, e quaisquer tags fora da whitelist.
 *
 * O conteúdo continua sendo autorado por administradores, mas a sanitização
 * é uma camada de defesa em profundidade contra XSS armazenado.
 */
class HtmlSanitizer
{
    /** Tags permitidas e seus atributos permitidos. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'mark' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'pre' => [], 'code' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
        'span' => [], 'div' => [],
        'iframe' => ['src', 'width', 'height', 'allow', 'allowfullscreen', 'frameborder', 'title'],
    ];

    /** Hosts permitidos para <iframe> (embed de vídeo). */
    private const IFRAME_HOSTS = [
        'www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com',
        'player.vimeo.com',
    ];

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        if (class_exists(\DOMDocument::class)) {
            return self::cleanWithDom($html);
        }

        return self::cleanFallback($html);
    }

    private static function cleanWithDom(string $html): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Preserva UTF-8 e evita que o parser injete DOCTYPE/html/body na saída.
        $wrapped = '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>';

        libxml_use_internal_errors(true);
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->getElementById('__root__');
        if (!$root) {
            return self::cleanFallback($html);
        }

        self::sanitizeNode($dom, $root);

        // Serializa apenas os filhos do root
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }
        return trim($out);
    }

    /**
     * Percorre recursivamente removendo tags/atributos não permitidos.
     */
    private static function sanitizeNode(\DOMDocument $dom, \DOMNode $node): void
    {
        // Copia porque vamos modificar a lista durante a iteração
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $child->parentNode->removeChild($child);
                continue;
            }

            if (!($child instanceof \DOMElement)) {
                continue; // nós de texto: mantidos (o DOM já escapa entidades)
            }

            $tag = strtolower($child->nodeName);

            if (!array_key_exists($tag, self::ALLOWED)) {
                // Tag não permitida: remove a tag preservando o texto interno
                self::unwrap($child);
                continue;
            }

            // Limpa atributos
            self::cleanAttributes($child, $tag);

            // Recursão
            self::sanitizeNode($dom, $child);
        }
    }

    private static function cleanAttributes(\DOMElement $el, string $tag): void
    {
        $allowedAttrs = self::ALLOWED[$tag];

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);

            // Remove qualquer handler de evento e atributos fora da whitelist
            if (strpos($name, 'on') === 0 || !in_array($name, $allowedAttrs, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            $value = trim($attr->nodeValue);

            // Sanitiza URLs em href/src
            if (in_array($name, ['href', 'src'], true) && !self::isSafeUrl($value)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }
        }

        // iframe só de hosts permitidos
        if ($tag === 'iframe') {
            $src = $el->getAttribute('src');
            $host = parse_url($src, PHP_URL_HOST);
            if (!$host || !in_array(strtolower($host), self::IFRAME_HOSTS, true)) {
                self::unwrap($el);
                return;
            }
        }

        // Links externos ganham rel de segurança quando target=_blank
        if ($tag === 'a' && strtolower($el->getAttribute('target')) === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }

        // Imagens: lazy loading por padrão
        if ($tag === 'img' && !$el->getAttribute('loading')) {
            $el->setAttribute('loading', 'lazy');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }
        // Permite caminhos relativos, âncoras, mailto e http(s)
        if (preg_match('#^(https?:)?//#i', $url)) {
            return true;
        }
        if ($url[0] === '/' || $url[0] === '#') {
            return true;
        }
        if (stripos($url, 'mailto:') === 0) {
            return true;
        }
        // Bloqueia javascript:, data:, vbscript:, file: etc.
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return false;
        }
        return true; // caminho relativo simples
    }

    /**
     * Substitui um elemento pelos seus filhos (remove a tag, mantém o conteúdo).
     */
    private static function unwrap(\DOMElement $el): void
    {
        $parent = $el->parentNode;
        if (!$parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    /**
     * Fallback sem ext-dom: mantém apenas tags simples e remove atributos.
     */
    private static function cleanFallback(string $html): string
    {
        // Remove blocos perigosos por completo
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*/?>#is', '', $html);

        $allowedTags = '<' . implode('><', array_keys(self::ALLOWED)) . '>';
        $html = strip_tags($html, $allowedTags);

        // Remove atributos on* e URLs perigosas de forma conservadora
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\')#i', '', $html);

        return trim($html);
    }
}
