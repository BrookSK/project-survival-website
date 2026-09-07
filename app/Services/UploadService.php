<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;

/**
 * Serviço de upload seguro de imagens.
 *
 * Valida extensão, MIME real (finfo/getimagesize), tamanho e integridade do
 * arquivo. Gera nomes aleatórios e nunca preserva o nome original enviado.
 * Uploads ficam em public/uploads/<subdir>/AAAA/MM.
 */
class UploadService
{
    private array $errors = [];

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Processa um upload de imagem.
     *
     * @param array  $file    Entrada de $_FILES['campo'].
     * @param string $subdir  Subpasta lógica (ex.: 'news', 'gallery', 'banners').
     * @return string|null    Caminho relativo salvo (ex.: 'news/2026/09/abc.webp') ou null em falha.
     */
    public function image(array $file, string $subdir = 'misc'): ?string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            $this->errors[] = 'Envio inválido.';
            return null;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = 'Nenhum arquivo enviado.';
                return null;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = 'O arquivo excede o tamanho permitido.';
                return null;
            default:
                $this->errors[] = 'Falha no upload do arquivo.';
                return null;
        }

        // Tamanho máximo
        $maxSize = (int) Config::get('app.uploads.max_size', 5 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            $this->errors[] = 'O arquivo excede o tamanho máximo de ' . round($maxSize / 1048576, 1) . ' MB.';
            return null;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = 'Arquivo temporário inválido.';
            return null;
        }

        // Defesa em profundidade: rejeita nomes com extensões executáveis conhecidas,
        // mesmo que o MIME também seja validado adiante.
        $dangerous = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar',
            'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd',
            'htaccess', 'htm', 'html', 'svg', 'js',
        ];
        $originalName = strtolower((string) ($file['name'] ?? ''));
        foreach (explode('.', $originalName) as $part) {
            if (in_array($part, $dangerous, true)) {
                $this->errors[] = 'Tipo de arquivo não permitido.';
                Logger::warning('Upload bloqueado por extensão suspeita: ' . $originalName);
                return null;
            }
        }

        // MIME real (não confia no enviado pelo navegador)
        $allowed = Config::get('app.uploads.allowed_mimes', []);
        $mime = $this->detectMime($file['tmp_name']);

        if ($mime === null || !isset($allowed[$mime])) {
            $this->errors[] = 'Tipo de arquivo não permitido. Envie uma imagem (JPG, PNG, WEBP ou GIF).';
            return null;
        }

        // Confirma que é realmente uma imagem
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->errors[] = 'O arquivo enviado não é uma imagem válida.';
            return null;
        }

        // Extensão segura derivada do MIME (não do nome original)
        $extensions = $allowed[$mime];
        $extension = $extensions[0];

        // Destino: public/uploads/<subdir>/AAAA/MM
        $subdir = preg_replace('/[^a-z0-9_-]/i', '', $subdir) ?: 'misc';
        $relativeDir = $subdir . '/' . date('Y') . '/' . date('m');
        $absoluteDir = PUBLIC_UPLOADS_PATH . '/' . $relativeDir;

        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            $this->errors[] = 'Não foi possível criar o diretório de upload.';
            Logger::error('Falha ao criar diretório de upload: ' . $absoluteDir);
            return null;
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $relativePath = $relativeDir . '/' . $filename;
        $absolutePath = $absoluteDir . '/' . $filename;

        if (!@move_uploaded_file($file['tmp_name'], $absolutePath)) {
            $this->errors[] = 'Não foi possível salvar o arquivo enviado.';
            Logger::error('Falha ao mover upload para: ' . $absolutePath);
            return null;
        }

        @chmod($absolutePath, 0644);

        return $relativePath;
    }

    /**
     * Remove um arquivo previamente enviado (caminho relativo a public/uploads).
     */
    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        // Impede path traversal
        $relativePath = str_replace('..', '', $relativePath);
        $absolute = PUBLIC_UPLOADS_PATH . '/' . ltrim($relativePath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Detecta o MIME real do arquivo usando finfo, com fallback para getimagesize.
     */
    private function detectMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if ($mime) {
                    return $mime;
                }
            }
        }

        $info = @getimagesize($path);
        return $info['mime'] ?? null;
    }
}
