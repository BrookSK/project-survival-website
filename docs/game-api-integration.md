# Integração comercial com a API do jogo

Este documento resume **como o site se integra à API do jogo na camada
comercial** e o que ainda depende do lado da API do jogo (Node). O contrato
detalhado está em [`docs/api/commercial-integration.md`](api/commercial-integration.md).

## Fronteira de responsabilidade

| Responsabilidade | Onde vive |
| ---------------- | --------- |
| Catálogo, preço/moeda oficiais, `owned` | **API do jogo** |
| Entitlement/inventário (conceder/revogar) | **API do jogo (exclusivo)** |
| Pedido, snapshot de preço, transações, cupons | **Site (PHP)** |
| Checkout e comunicação com o provedor de pagamento | **Site** |
| Webhook de pagamento + **concessão** de entitlement | **API do jogo** (`POST /payments/webhooks/:provider`) |
| Confirmação de posse | **Site** consulta `GET /player/entitlements` |

## O que a API do jogo já expõe (consumido hoje)

- `GET /store/products`, `GET /store/products/:id` — catálogo e preço/moeda.
- `GET /player/entitlements` — o que o jogador possui (fonte de verdade).
- `POST /store/purchase` — inicia um pedido `pending` (não cobra, não concede).

## Concessão (modelo oficial: webhook da Game API)

Verificado contra o repositório da API do jogo
(`BrookSK/project-survival-game`, `docs/API_SITE_CONTRACT.md` /
`INTEGRATION_SITE.md`). O modelo **oficial** de concessão é:

```
SITE → CHECKOUT → PROVEDOR DE PAGAMENTO → WEBHOOK → GAME API → ENTITLEMENT → GAME
```

- `POST /payments/webhooks/:provider` (na **Game API**, autenticado por
  `x-webhook-signature`): a API valida a assinatura, confirma o pedido e
  **concede** o entitlement — de forma **idempotente** (`order_id` +
  `provider_transaction_id`). Resposta:
  `{ received, verified, confirmed, alreadyPaid, granted:[…] }`.
- O **site não concede** itens. Ele orquestra o checkout, fala com o provedor de
  pagamento e registra `order`/`payment`/timeline. A posse é confirmada
  consultando `GET /player/entitlements`.

No site isso é representado pelo modo de fulfillment `game_webhook`
(`NullFulfillmentAdapter`), que é o **padrão**: não chama endpoint de concessão,
apenas registra a delegação na timeline. Configurável em
`fulfillment_mode` (setting).

### Modo alternativo (opcional): `commerce_endpoint`

Caso a Game API venha a expor um endpoint dedicado de concessão
server-to-server, o site já tem a abstração (`GameFulfillmentService`,
`Idempotency-Key = referência:produto`). **Não** deve ser ativado sem contrato
oficial. Detalhes e payloads em
[`docs/api/commercial-integration.md`](api/commercial-integration.md).

## Preço

O preço final cobrado é do **sistema comercial/pagamento do site** (a Game API
não é autoridade sobre preço). O catálogo/produto (id, nome, tipo, raridade) vem
da Game API; o navegador nunca define preço/total.

## Estado atual

A concessão acontece na Game API via webhook do provedor. O site exibe a posse a
partir de `GET /player/entitlements` e nunca concede de forma fictícia. Se a
integração estiver desligada/em manutenção, o site degrada com mensagens
amigáveis, sem quebrar.
