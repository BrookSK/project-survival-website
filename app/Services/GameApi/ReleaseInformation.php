<?php

namespace App\Services\GameApi;

/**
 * Informações de uma release do jogo, conforme o contrato oficial da Game API
 * (`GET /public/releases/latest` · `GET /public/releases/{channel}` ·
 * `GET /public/download{/channel}`).
 *
 * DTO imutável: representa EXATAMENTE os campos oficiais (nada é inventado).
 * O site nunca é autoridade sobre versão/hash/tamanho — tudo vem daqui.
 *
 * Contrato (docs do jogo: API_SITE_CONTRACT.md / PUBLIC_DOWNLOAD.md):
 *   product, channel, version, platform, mandatory, notes, released_at,
 *   requirements{os,arch,graphics,internet},
 *   installer{filename,url,permanent_url,sha256,size}, game{filename,url},
 *   download_url (apenas em /public/download).
 */
class ReleaseInformation
{
    /**
     * @param array $requirements ['os'=>,'arch'=>,'graphics'=>,'internet'=>]
     * @param array $installer     ['filename'=>,'url'=>,'permanent_url'=>,'sha256'=>,'size'=>]
     * @param array $game          ['filename'=>,'url'=>]
     */
    public function __construct(
        public readonly string $product,
        public readonly string $channel,
        public readonly string $version,
        public readonly string $platform,
        public readonly bool $mandatory,
        public readonly ?string $notes,
        public readonly ?string $releasedAt,
        public readonly array $requirements,
        public readonly array $installer,
        public readonly array $game,
        public readonly ?string $downloadUrl
    ) {
    }

    /**
     * Cria a partir do `data` do envelope (já desembrulhado do {success,data}).
     * Aceita tanto /public/releases/latest quanto /public/download (que
     * acrescenta download_url). Campos ausentes viram valores neutros — nunca
     * inventa versão nem URL.
     */
    public static function fromApi(array $data): self
    {
        $installer = is_array($data['installer'] ?? null) ? $data['installer'] : [];
        $game = is_array($data['game'] ?? null) ? $data['game'] : [];
        $requirements = is_array($data['requirements'] ?? null) ? $data['requirements'] : [];

        return new self(
            product: (string) ($data['product'] ?? 'Project Survival'),
            channel: (string) ($data['channel'] ?? 'stable'),
            version: (string) ($data['version'] ?? ''),
            platform: (string) ($data['platform'] ?? 'windows'),
            mandatory: (bool) ($data['mandatory'] ?? false),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            releasedAt: isset($data['released_at']) ? (string) $data['released_at'] : null,
            requirements: [
                'os'       => isset($requirements['os']) ? (string) $requirements['os'] : null,
                'arch'     => isset($requirements['arch']) ? (string) $requirements['arch'] : null,
                'graphics' => isset($requirements['graphics']) ? (string) $requirements['graphics'] : null,
                'internet' => isset($requirements['internet']) ? (string) $requirements['internet'] : null,
            ],
            installer: [
                'filename'      => isset($installer['filename']) ? (string) $installer['filename'] : null,
                'url'           => isset($installer['url']) ? (string) $installer['url'] : null,
                'permanent_url' => isset($installer['permanent_url']) ? (string) $installer['permanent_url'] : null,
                'sha256'        => isset($installer['sha256']) ? (string) $installer['sha256'] : null,
                'size'          => isset($installer['size']) && is_numeric($installer['size']) ? (int) $installer['size'] : null,
            ],
            game: [
                'filename' => isset($game['filename']) ? (string) $game['filename'] : null,
                'url'      => isset($game['url']) ? (string) $game['url'] : null,
            ],
            downloadUrl: isset($data['download_url']) ? (string) $data['download_url'] : null,
        );
    }

    /**
     * A release tem o mínimo para exibir/baixar (versão + alguma URL de instalador)?
     */
    public function isUsable(): bool
    {
        return $this->version !== '' && $this->bestInstallerUrl() !== null;
    }

    /**
     * Melhor URL para o botão de download: download_url > permanent_url > url.
     * O site fixa o botão nesta, sem hardcode de versão.
     */
    public function bestInstallerUrl(): ?string
    {
        return $this->downloadUrl
            ?: ($this->installer['permanent_url'] ?? null)
            ?: ($this->installer['url'] ?? null);
    }

    public function sha256(): ?string
    {
        return $this->installer['sha256'] ?? null;
    }

    public function sizeBytes(): ?int
    {
        return $this->installer['size'] ?? null;
    }

    /**
     * Tamanho formatado (MB) para exibição, ou null se desconhecido.
     */
    public function sizeLabel(): ?string
    {
        $bytes = $this->sizeBytes();
        if ($bytes === null || $bytes <= 0) {
            return null;
        }
        $mb = $bytes / (1024 * 1024);
        return number_format($mb, $mb >= 100 ? 0 : 1, ',', '.') . ' MB';
    }

    /**
     * Representação segura para serialização em cache/JSON (sem lógica).
     */
    public function toArray(): array
    {
        return [
            'product'      => $this->product,
            'channel'      => $this->channel,
            'version'      => $this->version,
            'platform'     => $this->platform,
            'mandatory'    => $this->mandatory,
            'notes'        => $this->notes,
            'released_at'  => $this->releasedAt,
            'requirements' => $this->requirements,
            'installer'    => $this->installer,
            'game'         => $this->game,
            'download_url' => $this->downloadUrl,
        ];
    }
}
