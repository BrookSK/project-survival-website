<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de vídeos/trailers.
 */
class Video extends Model
{
    protected string $table = 'videos';
    protected array $fillable = [
        'title', 'provider', 'url', 'video_id', 'thumbnail',
        'description', 'is_featured', 'is_active', 'sort_order',
    ];

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `videos` ORDER BY `sort_order` ASC, `id` DESC");
    }

    public function activeOrdered(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `videos` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` DESC"
        );
    }

    /**
     * Trailer principal (destaque) ou o primeiro vídeo ativo.
     */
    public function featured(): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM `videos` WHERE `is_active` = 1 AND `is_featured` = 1 ORDER BY `sort_order` ASC LIMIT 1"
        );
        if ($row) {
            return $row;
        }
        return $this->db->fetch("SELECT * FROM `videos` WHERE `is_active` = 1 ORDER BY `sort_order` ASC LIMIT 1");
    }

    /**
     * Extrai provedor + ID a partir de uma URL de YouTube/Vimeo.
     *
     * @return array{provider:string, video_id:?string, thumbnail:?string}
     */
    public static function parseUrl(string $url): array
    {
        $url = trim($url);

        // YouTube
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|v/)|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $m)) {
            $id = $m[1];
            return [
                'provider'  => 'youtube',
                'video_id'  => $id,
                'thumbnail' => "https://img.youtube.com/vi/{$id}/hqdefault.jpg",
            ];
        }

        // Vimeo
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
            return [
                'provider'  => 'vimeo',
                'video_id'  => $m[1],
                'thumbnail' => null,
            ];
        }

        return ['provider' => 'external', 'video_id' => null, 'thumbnail' => null];
    }

    /**
     * URL de embed apropriada para o provedor.
     */
    public static function embedUrl(array $video): string
    {
        if ($video['provider'] === 'youtube' && $video['video_id']) {
            return 'https://www.youtube-nocookie.com/embed/' . $video['video_id'];
        }
        if ($video['provider'] === 'vimeo' && $video['video_id']) {
            return 'https://player.vimeo.com/video/' . $video['video_id'];
        }
        return $video['url'];
    }
}
