-- =====================================================================
-- Migration: 026_create_privacy_tables
-- Descrição: Camada de privacidade/LGPD (Prompt 5): documentos legais
--            versionados, consentimentos, solicitações de titulares e
--            exportações de dados.
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

-- Documentos legais (Política de Privacidade, Termos, etc.). Um registro por
-- "tipo" de documento; o conteúdo vive nas versões (imutáveis após publicação).
CREATE TABLE IF NOT EXISTS `privacy_policies` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`       VARCHAR(40) NOT NULL COMMENT 'privacy, terms, purchase_terms, refund, cookies',
    `title`      VARCHAR(150) NOT NULL,
    `slug`       VARCHAR(150) NOT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Exige aceite do titular',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_privacy_policies_type` (`type`),
    UNIQUE KEY `uq_privacy_policies_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versões de cada documento. Uma versão publicada nunca é sobrescrita: novas
-- alterações criam uma nova versão. `status`: draft/published/archived.
CREATE TABLE IF NOT EXISTS `privacy_policy_versions` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `policy_id`    INT UNSIGNED NOT NULL,
    `version`      VARCHAR(20) NOT NULL COMMENT 'Ex.: 1.0, 1.1, 2.0',
    `content`      LONGTEXT NULL COMMENT 'HTML sanitizado',
    `status`       ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `effective_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de vigência',
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `published_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'users.id do responsável',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_policy_version` (`policy_id`, `version`),
    KEY `idx_ppv_status` (`policy_id`, `status`),
    CONSTRAINT `fk_ppv_policy` FOREIGN KEY (`policy_id`)
        REFERENCES `privacy_policies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ppv_publisher` FOREIGN KEY (`published_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consentimentos do titular (jogador). Vinculado ao player_id retornado pela
-- Game API (NÃO criamos tabela de jogador). Guarda o tipo, a versão do
-- documento/consentimento e quando foi aceito/revogado.
CREATE TABLE IF NOT EXISTS `privacy_consents` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id`    VARCHAR(64) NOT NULL COMMENT 'ID do jogador na Game API',
    `consent_type` VARCHAR(60) NOT NULL COMMENT 'terms, privacy, purchase_terms, marketing...',
    `version`      VARCHAR(20) NULL DEFAULT NULL COMMENT 'Versão do documento aceito',
    `granted`      TINYINT(1) NOT NULL DEFAULT 1,
    `source`       VARCHAR(40) NULL DEFAULT NULL COMMENT 'register, checkout, privacy_center...',
    `ip_address`   VARCHAR(45) NULL DEFAULT NULL,
    `user_agent`   VARCHAR(255) NULL DEFAULT NULL,
    `granted_at`   TIMESTAMP NULL DEFAULT NULL,
    `revoked_at`   TIMESTAMP NULL DEFAULT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_consent_player` (`player_id`),
    KEY `idx_consent_type` (`consent_type`),
    UNIQUE KEY `uq_consent_player_type` (`player_id`, `consent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitações de titulares (acesso, correção, exclusão, portabilidade, etc.).
CREATE TABLE IF NOT EXISTS `privacy_requests` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_id`   VARCHAR(40) NOT NULL COMMENT 'Identificador público/aleatório',
    `player_id`   VARCHAR(64) NULL DEFAULT NULL COMMENT 'ID do jogador na Game API',
    `email`       VARCHAR(190) NULL DEFAULT NULL COMMENT 'E-mail informado/associado',
    `type`        ENUM('access','correction','deletion','portability','consent_revocation','information','other') NOT NULL,
    `status`      ENUM('pending','in_review','awaiting_user','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `message`     TEXT NULL DEFAULT NULL COMMENT 'Descrição do titular',
    `admin_note`  TEXT NULL DEFAULT NULL COMMENT 'Nota interna (não copiar dados pessoais em excesso)',
    `handled_by`  INT UNSIGNED NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45) NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_privacy_requests_public` (`public_id`),
    KEY `idx_pr_status` (`status`),
    KEY `idx_pr_type` (`type`),
    KEY `idx_pr_player` (`player_id`),
    CONSTRAINT `fk_pr_handler` FOREIGN KEY (`handled_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportações de dados geradas para o titular. O arquivo fica FORA de /public,
-- com token de download e expiração.
CREATE TABLE IF NOT EXISTS `data_exports` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id`  VARCHAR(64) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL COMMENT 'Hash do token de download (nunca em claro)',
    `file_path`  VARCHAR(255) NULL DEFAULT NULL COMMENT 'Caminho privado (storage), nunca público',
    `status`     ENUM('pending','ready','expired','failed') NOT NULL DEFAULT 'pending',
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    `downloaded_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_data_exports_player` (`player_id`),
    KEY `idx_data_exports_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
