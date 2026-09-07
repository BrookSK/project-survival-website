<?php

namespace App\Models;

use App\Core\Model;

/**
 * Consentimentos do titular (jogador), vinculados ao player_id da Game API.
 *
 * Um consentimento por (player_id, consent_type): registrar/atualizar usa
 * "upsert". Guarda a versão do documento aceito e IP/user agent para prova.
 */
class PrivacyConsent extends Model
{
    protected string $table = 'privacy_consents';
    protected array $fillable = [
        'player_id', 'consent_type', 'version', 'granted', 'source',
        'ip_address', 'user_agent', 'granted_at', 'revoked_at',
    ];

    /**
     * Registra (ou atualiza) um consentimento. granted=false registra revogação.
     */
    public function record(
        string $playerId,
        string $type,
        bool $granted,
        ?string $version = null,
        string $source = 'privacy_center',
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            "INSERT INTO `privacy_consents`
                (`player_id`, `consent_type`, `version`, `granted`, `source`, `ip_address`, `user_agent`, `granted_at`, `revoked_at`)
             VALUES (:pid, :type, :ver, :granted, :source, :ip, :ua, :grantedAt, :revokedAt)
             ON DUPLICATE KEY UPDATE
                `version` = VALUES(`version`),
                `granted` = VALUES(`granted`),
                `source` = VALUES(`source`),
                `ip_address` = VALUES(`ip_address`),
                `user_agent` = VALUES(`user_agent`),
                `granted_at` = VALUES(`granted_at`),
                `revoked_at` = VALUES(`revoked_at`)",
            [
                'pid' => $playerId,
                'type' => $type,
                'ver' => $version,
                'granted' => $granted ? 1 : 0,
                'source' => $source,
                'ip' => $ip,
                'ua' => $userAgent ? substr($userAgent, 0, 255) : null,
                'grantedAt' => $granted ? $now : null,
                'revokedAt' => $granted ? null : $now,
            ]
        );
    }

    /**
     * Todos os consentimentos de um jogador.
     */
    public function forPlayer(string $playerId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `privacy_consents` WHERE `player_id` = :pid ORDER BY `consent_type` ASC",
            ['pid' => $playerId]
        );
    }

    /**
     * Verifica se um consentimento específico está concedido.
     */
    public function has(string $playerId, string $type): bool
    {
        $row = $this->db->fetch(
            "SELECT `granted` FROM `privacy_consents` WHERE `player_id` = :pid AND `consent_type` = :type LIMIT 1",
            ['pid' => $playerId, 'type' => $type]
        );
        return $row !== null && (int) $row['granted'] === 1;
    }

    /**
     * Versão aceita de um consentimento (ou null se não concedido).
     */
    public function acceptedVersion(string $playerId, string $type): ?string
    {
        $row = $this->db->fetch(
            "SELECT `version`, `granted` FROM `privacy_consents` WHERE `player_id` = :pid AND `consent_type` = :type LIMIT 1",
            ['pid' => $playerId, 'type' => $type]
        );
        if (!$row || (int) $row['granted'] !== 1) {
            return null;
        }
        return $row['version'];
    }

    /**
     * Visão agregada: contagem de consentimentos concedidos por tipo.
     *
     * @return array<string,int>
     */
    public function summaryByType(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT `consent_type`, SUM(`granted` = 1) AS granted, COUNT(*) AS total
             FROM `privacy_consents` GROUP BY `consent_type` ORDER BY `consent_type` ASC"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r['consent_type']] = ['granted' => (int) $r['granted'], 'total' => (int) $r['total']];
        }
        return $out;
    }

    /**
     * Remove os consentimentos de um jogador (usado na exclusão de conta).
     */
    public function purgePlayer(string $playerId): int
    {
        return $this->db->execute(
            "DELETE FROM `privacy_consents` WHERE `player_id` = :pid",
            ['pid' => $playerId]
        );
    }
}
