<?php

namespace App\Models;

use App\Core\Model;

/**
 * Documento legal (Política de Privacidade, Termos, etc.) e suas versões.
 *
 * Uma versão publicada é imutável: alterações criam uma nova versão. A leitura
 * pública sempre usa a versão publicada mais recente (menor risco de expor
 * rascunhos).
 */
class PrivacyPolicy extends Model
{
    protected string $table = 'privacy_policies';
    protected array $fillable = ['type', 'title', 'slug', 'is_required'];

    public function findByType(string $type): ?array
    {
        return $this->findBy('type', $type);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `privacy_policies` ORDER BY `id` ASC");
    }

    /**
     * Versão publicada vigente de um documento (por policy id).
     * Considera effective_at <= agora (ou nulo) e status publicado.
     */
    public function publishedVersion(int $policyId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `privacy_policy_versions`
             WHERE `policy_id` = :pid AND `status` = 'published'
               AND (`effective_at` IS NULL OR `effective_at` <= NOW())
             ORDER BY `published_at` DESC, `id` DESC LIMIT 1",
            ['pid' => $policyId]
        );
    }

    /**
     * Documento + versão publicada, buscando por slug (para páginas públicas).
     */
    public function publishedBySlug(string $slug): ?array
    {
        $policy = $this->findBySlug($slug);
        if (!$policy) {
            return null;
        }
        $version = $this->publishedVersion((int) $policy['id']);
        if (!$version) {
            return null;
        }
        return ['policy' => $policy, 'version' => $version];
    }

    /**
     * Todas as versões de um documento (histórico), mais recentes primeiro.
     */
    public function versions(int $policyId): array
    {
        return $this->db->fetchAll(
            "SELECT v.*, u.name AS publisher_name
             FROM `privacy_policy_versions` v
             LEFT JOIN `users` u ON u.id = v.published_by
             WHERE v.`policy_id` = :pid
             ORDER BY v.`id` DESC",
            ['pid' => $policyId]
        );
    }

    public function findVersion(int $versionId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `privacy_policy_versions` WHERE `id` = :id LIMIT 1",
            ['id' => $versionId]
        );
    }

    /**
     * Cria uma nova versão (rascunho) para um documento.
     */
    public function createVersion(int $policyId, string $version, string $content, ?string $effectiveAt = null): int
    {
        $this->db->execute(
            "INSERT INTO `privacy_policy_versions` (`policy_id`, `version`, `content`, `status`, `effective_at`)
             VALUES (:pid, :ver, :content, 'draft', :eff)",
            ['pid' => $policyId, 'ver' => $version, 'content' => $content, 'eff' => $effectiveAt]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateDraft(int $versionId, string $content, ?string $effectiveAt = null): int
    {
        return $this->db->execute(
            "UPDATE `privacy_policy_versions` SET `content` = :content, `effective_at` = :eff
             WHERE `id` = :id AND `status` = 'draft'",
            ['content' => $content, 'eff' => $effectiveAt, 'id' => $versionId]
        );
    }

    /**
     * Publica uma versão (rascunho -> publicado), arquivando as anteriores
     * publicadas do mesmo documento. Transacional.
     */
    public function publishVersion(int $versionId, int $userId): bool
    {
        return (bool) $this->db->transaction(function (\App\Core\Database $db) use ($versionId, $userId) {
            $version = $db->fetch("SELECT * FROM `privacy_policy_versions` WHERE `id` = :id LIMIT 1", ['id' => $versionId]);
            if (!$version) {
                return false;
            }
            $policyId = (int) $version['policy_id'];

            // Arquiva versões publicadas anteriores.
            $db->execute(
                "UPDATE `privacy_policy_versions` SET `status` = 'archived'
                 WHERE `policy_id` = :pid AND `status` = 'published' AND `id` <> :id",
                ['pid' => $policyId, 'id' => $versionId]
            );

            // Publica esta versão (preserva effective_at agendado, se houver).
            $db->execute(
                "UPDATE `privacy_policy_versions`
                 SET `status` = 'published', `published_at` = NOW(), `published_by` = :uid,
                     `effective_at` = COALESCE(`effective_at`, NOW())
                 WHERE `id` = :id",
                ['uid' => $userId, 'id' => $versionId]
            );
            return true;
        });
    }
}
