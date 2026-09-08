-- =====================================================================
-- Migration: 027_create_commerce_tables
-- Descrição: Camada comercial (Prompt 6): pedidos, itens, transações de
--            pagamento, eventos de webhook (idempotência), timeline de
--            pedidos, fulfillments (concessão via Game API) e cupons.
--
-- Regras de ouro refletidas no schema:
--   * Dinheiro SEMPRE em inteiro (centavos). Nunca float.
--   * Estados separados: order_status, payment_status, fulfillment_status.
--   * Entitlement/inventário NÃO vivem aqui — são autoridade da Game API.
--     Este banco só registra a INTENÇÃO/RESULTADO da concessão (fulfillment),
--     nunca os itens concedidos ao jogador.
--   * player_id é o ID do jogador na Game API (VARCHAR), não FK local.
--   * Idempotência: webhook_events.event_id UNIQUE, fulfillments.idempotency_key UNIQUE.
--
-- IMUTÁVEL: nunca edite este arquivo. Alterações exigem nova migration.
-- =====================================================================

-- ---------------------------------------------------------------------
-- orders: um pedido do jogador. Snapshot de totais em centavos.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`        VARCHAR(40) NOT NULL COMMENT 'Referência pública/estável do pedido (ex.: SITE-000123)',
    `player_id`        VARCHAR(64) NOT NULL COMMENT 'ID do jogador na Game API (autoridade da conta)',
    `player_email`     VARCHAR(190) NULL DEFAULT NULL COMMENT 'E-mail no momento do pedido (para notificação/suporte)',
    `order_status`     ENUM('pending','awaiting_payment','paid','cancelled','refunded','partially_refunded','failed','expired') NOT NULL DEFAULT 'pending',
    `payment_status`   ENUM('none','pending','in_process','approved','rejected','cancelled','refunded','charged_back') NOT NULL DEFAULT 'none',
    `fulfillment_status` ENUM('none','pending','processing','fulfilled','failed','revoked') NOT NULL DEFAULT 'none',
    `currency`         VARCHAR(3) NOT NULL DEFAULT 'BRL',
    `subtotal_cents`   INT UNSIGNED NOT NULL DEFAULT 0,
    `discount_cents`   INT UNSIGNED NOT NULL DEFAULT 0,
    `total_cents`      INT UNSIGNED NOT NULL DEFAULT 0,
    `coupon_id`        INT UNSIGNED NULL DEFAULT NULL,
    `coupon_code`      VARCHAR(40) NULL DEFAULT NULL COMMENT 'Snapshot do código aplicado',
    `gateway`          VARCHAR(30) NULL DEFAULT NULL COMMENT 'Provedor de pagamento (ex.: mercadopago)',
    `terms_version`    VARCHAR(20) NULL DEFAULT NULL COMMENT 'Versão dos termos de compra aceitos',
    `refund_terms_version` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Versão da política de reembolso aceita',
    `idempotency_key`  VARCHAR(80) NULL DEFAULT NULL COMMENT 'Anti-duplo-clique do checkout (por jogador+carrinho)',
    `client_ip`        VARCHAR(45) NULL DEFAULT NULL,
    `user_agent`       VARCHAR(255) NULL DEFAULT NULL,
    `paid_at`          TIMESTAMP NULL DEFAULT NULL,
    `expires_at`       TIMESTAMP NULL DEFAULT NULL COMMENT 'Expiração da intenção de pagamento (ex.: PIX)',
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_reference` (`reference`),
    UNIQUE KEY `uq_orders_idempotency` (`idempotency_key`),
    KEY `idx_orders_player` (`player_id`),
    KEY `idx_orders_order_status` (`order_status`),
    KEY `idx_orders_payment_status` (`payment_status`),
    KEY `idx_orders_fulfillment_status` (`fulfillment_status`),
    KEY `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- order_items: linhas do pedido. Snapshot IMUTÁVEL de preço/moeda vindo
-- da Game API no momento do pedido (o navegador NUNCA informa preço).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`       BIGINT UNSIGNED NOT NULL,
    `product_id`     VARCHAR(64) NOT NULL COMMENT 'ID do produto na Game API',
    `sku`            VARCHAR(80) NULL DEFAULT NULL,
    `name`           VARCHAR(190) NOT NULL COMMENT 'Snapshot do nome no momento do pedido',
    `unit_price_cents` INT UNSIGNED NOT NULL COMMENT 'Preço unitário oficial (Game API), em centavos',
    `currency`       VARCHAR(3) NOT NULL DEFAULT 'BRL',
    `quantity`       INT UNSIGNED NOT NULL DEFAULT 1,
    `line_total_cents` INT UNSIGNED NOT NULL COMMENT 'unit_price_cents * quantity',
    `metadata`       JSON NULL DEFAULT NULL COMMENT 'Snapshot adicional (rarity, entitlements esperados) — não é fonte de verdade',
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- payment_transactions: cada tentativa/estado de pagamento junto ao
-- gateway. external_id é o ID no gateway (ex.: payment id do Mercado Pago).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`      BIGINT UNSIGNED NOT NULL,
    `gateway`       VARCHAR(30) NOT NULL COMMENT 'mercadopago, null...',
    `external_id`   VARCHAR(80) NULL DEFAULT NULL COMMENT 'ID do pagamento no gateway',
    `method`        VARCHAR(30) NULL DEFAULT NULL COMMENT 'pix, credit_card, etc.',
    `status`        ENUM('pending','in_process','approved','rejected','cancelled','refunded','charged_back') NOT NULL DEFAULT 'pending',
    `amount_cents`  INT UNSIGNED NOT NULL DEFAULT 0,
    `currency`      VARCHAR(3) NOT NULL DEFAULT 'BRL',
    `refunded_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `raw_snapshot`  JSON NULL DEFAULT NULL COMMENT 'Snapshot sanitizado da consulta ao gateway (SEM segredos)',
    `processed_at`  TIMESTAMP NULL DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_tx_gateway_external` (`gateway`, `external_id`),
    KEY `idx_payment_tx_order` (`order_id`),
    KEY `idx_payment_tx_status` (`status`),
    CONSTRAINT `fk_payment_tx_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- webhook_events: log/idempotência dos webhooks recebidos do gateway.
-- event_id UNIQUE garante que 10 webhooks idênticos = 1 processamento.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `webhook_events` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider`      VARCHAR(30) NOT NULL COMMENT 'Gateway de origem (ex.: mercadopago)',
    `event_id`      VARCHAR(120) NOT NULL COMMENT 'ID único do evento no gateway (idempotência)',
    `event_type`    VARCHAR(60) NULL DEFAULT NULL COMMENT 'payment.updated, etc.',
    `external_ref`  VARCHAR(80) NULL DEFAULT NULL COMMENT 'ID do pagamento referenciado',
    `order_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `status`        ENUM('received','processed','ignored','invalid','failed') NOT NULL DEFAULT 'received',
    `signature_valid` TINYINT(1) NOT NULL DEFAULT 0,
    `payload`       JSON NULL DEFAULT NULL COMMENT 'Payload recebido (sem segredos; nunca é prova de pagamento)',
    `note`          VARCHAR(255) NULL DEFAULT NULL,
    `received_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_webhook_provider_event` (`provider`, `event_id`),
    KEY `idx_webhook_order` (`order_id`),
    KEY `idx_webhook_status` (`status`),
    CONSTRAINT `fk_webhook_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- order_events: timeline auditável do pedido (order.created, payment.approved,
-- fulfillment.requested, refund.done...). Apenas append.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_events` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`   BIGINT UNSIGNED NOT NULL,
    `type`       VARCHAR(60) NOT NULL COMMENT 'order.created, payment.approved, fulfillment.requested, etc.',
    `message`    VARCHAR(255) NULL DEFAULT NULL,
    `actor`      VARCHAR(60) NULL DEFAULT NULL COMMENT 'system, gateway:mercadopago, admin:<id>, player',
    `data`       JSON NULL DEFAULT NULL COMMENT 'Dados do evento (sem segredos)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_events_order` (`order_id`),
    KEY `idx_order_events_type` (`type`),
    CONSTRAINT `fk_order_events_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- fulfillments: registro da INTENÇÃO/RESULTADO de concessão via Game API.
-- NÃO guarda o entitlement em si (isso é autoridade da Game API).
-- idempotency_key UNIQUE = order_reference:product_id -> 1 concessão por item.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fulfillments` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`        BIGINT UNSIGNED NOT NULL,
    `order_item_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `product_id`      VARCHAR(64) NOT NULL COMMENT 'ID do produto na Game API',
    `player_id`       VARCHAR(64) NOT NULL,
    `idempotency_key` VARCHAR(160) NOT NULL COMMENT 'order_reference:product_id (idempotência da concessão)',
    `status`          ENUM('pending','processing','fulfilled','failed','revoked') NOT NULL DEFAULT 'pending',
    `attempts`        INT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts`    INT UNSIGNED NOT NULL DEFAULT 8,
    `external_id`     VARCHAR(80) NULL DEFAULT NULL COMMENT 'fulfillment_id retornado pela Game API',
    `last_error`      VARCHAR(255) NULL DEFAULT NULL COMMENT 'Motivo da última falha (sem segredos)',
    `permanent_failure` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = erro definitivo (não fazer retry)',
    `next_attempt_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Backoff: quando tentar novamente',
    `fulfilled_at`    TIMESTAMP NULL DEFAULT NULL,
    `revoked_at`      TIMESTAMP NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fulfillments_idempotency` (`idempotency_key`),
    KEY `idx_fulfillments_order` (`order_id`),
    KEY `idx_fulfillments_status` (`status`),
    KEY `idx_fulfillments_player` (`player_id`),
    KEY `idx_fulfillments_next_attempt` (`next_attempt_at`),
    CONSTRAINT `fk_fulfillments_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_fulfillments_item` FOREIGN KEY (`order_item_id`)
        REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- coupons: cupons de desconto validados SEMPRE no backend (nunca no browser).
-- Valor em centavos (fixo) ou percentual (0-100).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`           VARCHAR(40) NOT NULL,
    `description`    VARCHAR(190) NULL DEFAULT NULL,
    `type`           ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `percent_off`    DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'Para type=percent (0-100)',
    `amount_off_cents` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Para type=fixed, em centavos',
    `currency`       VARCHAR(3) NOT NULL DEFAULT 'BRL',
    `min_total_cents` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pedido mínimo para aplicar',
    `max_redemptions` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = ilimitado',
    `redeemed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `per_player_limit` INT UNSIGNED NULL DEFAULT NULL,
    `active`         TINYINT(1) NOT NULL DEFAULT 1,
    `starts_at`      TIMESTAMP NULL DEFAULT NULL,
    `ends_at`        TIMESTAMP NULL DEFAULT NULL,
    `created_by`     INT UNSIGNED NULL DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_coupons_code` (`code`),
    KEY `idx_coupons_active` (`active`),
    CONSTRAINT `fk_coupons_creator` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- coupon_redemptions: uso de cupom por pedido/jogador (controle de limite).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupon_redemptions` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `coupon_id`  INT UNSIGNED NOT NULL,
    `order_id`   BIGINT UNSIGNED NOT NULL,
    `player_id`  VARCHAR(64) NOT NULL,
    `discount_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_coupon_redemption_order` (`order_id`),
    KEY `idx_coupon_redemptions_coupon` (`coupon_id`),
    KEY `idx_coupon_redemptions_player` (`player_id`),
    CONSTRAINT `fk_coupon_redemptions_coupon` FOREIGN KEY (`coupon_id`)
        REFERENCES `coupons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_coupon_redemptions_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
