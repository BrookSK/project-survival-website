<?php

namespace App\Core;

/**
 * Autoloader PSR-4 simples.
 *
 * Mapeia o namespace raiz "App\" para o diretório app/.
 * Evita a necessidade de Composer para carregar classes do projeto.
 */
class Autoloader
{
    /** @var array<string,string> Prefixo de namespace => diretório base */
    private array $prefixes = [];

    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    /**
     * Registra um prefixo de namespace e o diretório correspondente.
     */
    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->prefixes[$prefix] = $baseDir;
    }

    /**
     * Tenta carregar o arquivo correspondente à classe.
     */
    public function load(string $class): void
    {
        foreach ($this->prefixes as $prefix => $baseDir) {
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
}
