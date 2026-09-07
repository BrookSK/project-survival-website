<?php

namespace App\Services;

use App\Models\PrivacyConsent;
use App\Models\PrivacyPolicy;

/**
 * Orquestra o registro de consentimentos do titular vinculados às versões
 * vigentes dos documentos legais, e detecta necessidade de reaceite.
 *
 * Consentimentos obrigatórios (Termos, Privacidade) são registrados no cadastro
 * com a versão publicada vigente. Marketing é opt-in separado. A versão aceita
 * fica gravada para que uma nova versão publicada acione um pedido de reaceite.
 */
class ConsentService
{
    private PrivacyPolicy $policies;
    private PrivacyConsent $consents;

    /** Documentos cujo aceite é obrigatório (por tipo => consent_type). */
    private const REQUIRED = ['terms' => 'terms', 'privacy' => 'privacy'];

    public function __construct()
    {
        $this->policies = new PrivacyPolicy();
        $this->consents = new PrivacyConsent();
    }

    /**
     * Versão publicada vigente de um tipo de documento (string) ou null.
     */
    public function currentVersion(string $type): ?string
    {
        $policy = $this->policies->findByType($type);
        if (!$policy) {
            return null;
        }
        $version = $this->policies->publishedVersion((int) $policy['id']);
        return $version['version'] ?? null;
    }

    /**
     * Registra o aceite dos documentos obrigatórios (na versão vigente) para um
     * jogador. Chamado após o cadastro/aceite explícito.
     */
    public function recordRequiredAcceptance(string $playerId, string $source, ?string $ip, ?string $userAgent): void
    {
        foreach (self::REQUIRED as $type => $consentType) {
            $this->consents->record(
                $playerId,
                $consentType,
                true,
                $this->currentVersion($type),
                $source,
                $ip,
                $userAgent
            );
        }
    }

    /**
     * Registra a preferência de marketing (opt-in/out).
     */
    public function recordMarketing(string $playerId, bool $granted, ?string $ip, ?string $userAgent): void
    {
        $this->consents->record($playerId, 'marketing', $granted, null, 'register', $ip, $userAgent);
    }

    /**
     * Verifica se o jogador precisa reaceitar algum documento obrigatório
     * (versão vigente diferente da aceita, ou nunca aceito).
     *
     * @return string[] tipos que exigem (re)aceite
     */
    public function pendingReacceptance(string $playerId): array
    {
        $pending = [];
        foreach (self::REQUIRED as $type => $consentType) {
            $current = $this->currentVersion($type);
            if ($current === null) {
                continue; // Documento ainda não publicado: não força aceite.
            }
            $accepted = $this->consents->acceptedVersion($playerId, $consentType);
            if ($accepted !== $current) {
                $pending[] = $type;
            }
        }
        return $pending;
    }
}
