<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

/**
 * Biblioteca de mídia + otimização de imagens.
 *
 * Recebe uploads, valida (reaproveitando as regras do UploadService),
 * gera variantes (thumbnail/medium/large) e versão WebP quando a extensão GD
 * está disponível, extrai dimensões e registra tudo na tabela `media`.
 *
 * Degrada com elegância: sem GD, salva apenas o original (sem variantes),
 * continuando plenamente funcional em hospedagem PHP básica.
 */
class MediaService
{
    /** Larguras-alvo das variantes (px). */
    private const SIZES = [
        'thumbnail' => 300,
        'medium'    => 800,
        'large'     => 1600,
    ];

    private array $errors = [];

    public function errors(): array
    {
        return $this->errors;
    }

    public static function gdAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    public static function webpSupported(): bool
    {
        return self::gdAvailable() && function_exists('imagewebp');
    }

    /**
     * Processa um upload de imagem e registra na biblioteca.
     *
     * @return int|null ID do registro em `media`, ou null em falha.
     */
    public function store(array $file, ?int $userId = null): ?int
    {
        // Reaproveita a validação segura do UploadService
        $uploader = new UploadService();
        $relativePath = $uploader->image($file, 'media');

        if ($relativePath === null) {
            $this->errors = $uploader->errors();
            return null;
        }

        $absolutePath = PUBLIC_UPLOADS_PATH . '/' . $relativePath;

        // Metadados
        $mime = $this->detectMime($absolutePath);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $size = (int) @filesize($absolutePath);
        [$width, $height] = $this->dimensions($absolutePath);

        // Gera variantes (best-effort)
        $variants = $this->generateVariants($absolutePath, $relativePath, $mime);

        try {
            Database::getInstance()->execute(
                "INSERT INTO `media`
                    (`filename`, `original_name`, `path`, `mime_type`, `extension`, `size`, `width`, `height`, `variants`, `uploaded_by`)
                 VALUES (:fn, :on, :path, :mime, :ext, :size, :w, :h, :variants, :uid)",
                [
                    'fn'       => basename($relativePath),
                    'on'       => substr((string) ($file['name'] ?? ''), 0, 255),
                    'path'     => $relativePath,
                    'mime'     => $mime,
                    'ext'      => $extension,
                    'size'     => $size,
                    'w'        => $width,
                    'h'        => $height,
                    'variants' => $variants ? json_encode($variants, JSON_UNESCAPED_SLASHES) : null,
                    'uid'      => $userId,
                ]
            );
            return (int) Database::getInstance()->lastInsertId();
        } catch (\Throwable $e) {
            Logger::exception($e);
            // Remove arquivos órfãos se o registro falhar
            $uploader->delete($relativePath);
            foreach ($variants as $v) {
                $uploader->delete($v);
            }
            $this->errors[] = 'Falha ao registrar a mídia.';
            return null;
        }
    }

    /**
     * Remove um item de mídia (registro + arquivos: original e variantes).
     */
    public function remove(array $media): void
    {
        $uploader = new UploadService();
        $uploader->delete($media['path'] ?? null);

        if (!empty($media['variants'])) {
            $variants = json_decode($media['variants'], true) ?: [];
            foreach ($variants as $path) {
                if (is_string($path)) {
                    $uploader->delete($path);
                }
            }
        }

        try {
            Database::getInstance()->execute("DELETE FROM `media` WHERE `id` = :id", ['id' => $media['id']]);
        } catch (\Throwable $e) {
            Logger::exception($e);
        }
    }

    /**
     * Gera variantes redimensionadas + WebP. Retorna [chave => caminho relativo].
     */
    private function generateVariants(string $absolutePath, string $relativePath, ?string $mime): array
    {
        if (!self::gdAvailable() || $mime === 'image/gif') {
            return []; // GIF (possível animação) e ambientes sem GD: mantém original
        }

        $src = $this->createImage($absolutePath, $mime);
        if (!$src) {
            return [];
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $dir = dirname($relativePath);
        $base = pathinfo($relativePath, PATHINFO_FILENAME);
        $variants = [];

        foreach (self::SIZES as $key => $targetW) {
            // Não amplia imagens menores que o alvo
            if ($origW <= $targetW && $key !== 'thumbnail') {
                continue;
            }
            $ratio = $targetW / $origW;
            $newW = $targetW;
            $newH = (int) round($origH * $ratio);
            if ($origW <= $targetW) {
                $newW = $origW;
                $newH = $origH;
            }

            $resized = imagecreatetruecolor($newW, $newH);
            $this->preserveTransparency($resized, $mime);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            // Prefere WebP quando suportado
            if (self::webpSupported()) {
                $variantPath = $dir . '/' . $base . '-' . $key . '.webp';
                $out = PUBLIC_UPLOADS_PATH . '/' . $variantPath;
                if (@imagewebp($resized, $out, 82)) {
                    $variants[$key] = $variantPath;
                }
            } else {
                $variantPath = $dir . '/' . $base . '-' . $key . '.' . pathinfo($relativePath, PATHINFO_EXTENSION);
                $out = PUBLIC_UPLOADS_PATH . '/' . $variantPath;
                if ($this->saveImage($resized, $out, $mime)) {
                    $variants[$key] = $variantPath;
                }
            }
            imagedestroy($resized);
        }

        imagedestroy($src);
        return $variants;
    }

    private function createImage(string $path, ?string $mime)
    {
        switch ($mime) {
            case 'image/jpeg': return @imagecreatefromjpeg($path);
            case 'image/png':  return @imagecreatefrompng($path);
            case 'image/webp': return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            default:           return false;
        }
    }

    private function saveImage($img, string $path, ?string $mime): bool
    {
        switch ($mime) {
            case 'image/jpeg': return @imagejpeg($img, $path, 85);
            case 'image/png':  return @imagepng($img, $path, 6);
            case 'image/webp': return function_exists('imagewebp') ? @imagewebp($img, $path, 82) : false;
            default:           return false;
        }
    }

    private function preserveTransparency($img, ?string $mime): void
    {
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $transparent);
        }
    }

    private function dimensions(string $path): array
    {
        $info = @getimagesize($path);
        return [$info[0] ?? null, $info[1] ?? null];
    }

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
