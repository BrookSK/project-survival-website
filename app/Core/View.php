<?php

namespace App\Core;

/**
 * Renderizador de views baseado em PHP puro.
 *
 * As views ficam em app/Views. Suporta layouts: a view define o conteúdo
 * e um layout envolve esse conteúdo. Fornece o helper e() para escaping.
 */
class View
{
    private static array $shared = [];

    /**
     * Compartilha dados com todas as views (ex.: settings do site, usuário logado).
     */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(): array
    {
        return self::$shared;
    }

    /**
     * Renderiza uma view e retorna o HTML como string.
     *
     * @param string $view  Caminho relativo com pontos (ex.: 'site.home').
     * @param array  $data  Variáveis disponíveis na view.
     * @param string|null $layout Layout envolvente (ex.: 'layouts.site'), ou null.
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $data = array_merge(self::$shared, $data);
        $content = self::capture($view, $data);

        if ($layout !== null) {
            $data['content'] = $content;
            return self::capture($layout, $data);
        }

        return $content;
    }

    /**
     * Executa o arquivo da view isolando o escopo e capturando a saída.
     */
    private static function capture(string $view, array $data): string
    {
        $file = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    /**
     * Renderiza uma partial dentro de outra view (inclui e retorna string).
     */
    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, array_merge(self::$shared, $data));
    }
}
