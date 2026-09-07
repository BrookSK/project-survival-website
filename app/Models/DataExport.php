<?php

namespace App\Models;

use App\Core\Model;

/**
 * Exportações de dados do titular (portabilidade/acesso).
 *
 * O arquivo é gravado FORA de /public (em storage privado). O download exige
 * um token cujo hash é guardado aqui; o token em claro só existe na URL enviada
 * ao titular autenticado e a exportação expira.
 */
class DataExport extends Model
{
    protected string $table = 'data_exports';
    protected array $fillable = [
        'player_id', 'token_hash', 'file_path', 'status', 'expires_at', 'downloaded_at',
    ];

    /**
     * Cria um registro de exportação pronta, guardando o hash do token.
     * Retorna o token em claro (para compor o link) e o id.
     */
    public function createReady(string $playerId, string $filePath, int $ttlDays): array
    {
        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', time() + max(1, $ttlDays) * 86400);
        $id = $this->create([
            'player_id'  => $playerId,
            'token_hash' => hash('sha256', $token),
            'file_path'  => $filePath,
            'status'     => 'ready',
            'expires_at' => $expiresAt,
        ]);
        return ['id' => $id, 'token' => $token, 'expires_at' => $expiresAt];
    }

    /**
     * Localiza uma exportação válida (não expirada) para um jogador por token.
     */
    public function findValidForPlayer(string $playerId, string $token): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM `data_exports`
             WHERE `player_id` = :pid AND `token_hash` = :th AND `status` = 'ready'
               AND (`expires_at` IS NULL OR `expires_at` >= NOW())
             LIMIT 1",
            ['pid' => $playerId, 'th' => hash('sha256', $token)]
        );
        return $row ?: null;
    }

    public function markDownloaded(int $id): void
    {
        $this->db->execute(
            "UPDATE `data_exports` SET `downloaded_at` = NOW() WHERE `id` = :id",
            ['id' => $id]
        );
    }

    public function latestForPlayer(string $playerId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `data_exports` WHERE `player_id` = :pid ORDER BY `id` DESC LIMIT 1",
            ['pid' => $playerId]
        );
    }

    /**
     * Exportações recentes (para o admin), SEM expor o token_hash/token.
     */
    public function recent(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        return $this->db->fetchAll(
            "SELECT `id`, `player_id`, `status`, `expires_at`, `downloaded_at`, `created_at`
             FROM `data_exports` ORDER BY `id` DESC LIMIT {$limit}"
        );
    }

    /**
     * Marca exportações expiradas e devolve os caminhos para remoção física.
     *
     * @return string[] caminhos de arquivo das exportações expiradas
     */
    public function expireOld(): array
    {
        $expired = $this->db->fetchAll(
            "SELECT `id`, `file_path` FROM `data_exports`
             WHERE `status` = 'ready' AND `expires_at` IS NOT NULL AND `expires_at` < NOW()"
        );
        if ($expired) {
            $this->db->execute(
                "UPDATE `data_exports` SET `status` = 'expired'
                 WHERE `status` = 'ready' AND `expires_at` IS NOT NULL AND `expires_at` < NOW()"
            );
        }
        return array_values(array_filter(array_map(static fn ($r) => $r['file_path'] ?? null, $expired)));
    }
}
