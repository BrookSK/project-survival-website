# Integração comercial com a API do jogo

Este documento resume **como o site se integra à API do jogo na camada
comercial** e o que ainda depende do lado da API do jogo (Node). O contrato
detalhado está em [`docs/api/commercial-integration.md`](api/commercial-integration.md).

## Fronteira de responsabilidade

| Responsabilidade | Onde vive |
| ---------------- | --------- |
| Catálogo, preço/moeda oficiais, `owned` | **API do jogo** |
| Entitlement/inventário (conceder/revogar) | **API do jogo (exclusivo)** |
| Pedido, snapshot de preço, transações, webhooks, cupons | **Site (PHP)** |
| Comunicação com o gateway (Mercado Pago) | **Site** |
| Confirmação do estado do pagamento | **Site** consulta o gateway |
| Solicitação de concessão/revogação | **Site → API do jogo** (service account) |

## O que a API do jogo já expõe (consumido hoje)

- `GET /store/products`, `GET /store/products/:id` — catálogo e preço/moeda.
- `GET /player/entitlements` — o que o jogador possui (fonte de verdade).
- `POST /store/purchase` — inicia um pedido `pending` (não cobra, não concede).

Já existe (mas ainda não usado pela camada comercial do site):

- `POST /admin/entitlements/grant` — concede entitlement (token de admin, RBAC
  `SUPPORT`/`SUPER_ADMIN`).
- `POST /payments/webhooks/:provider` — webhook **stub** (valida assinatura; sem
  assinatura válida, nada é concedido).

## Concessão server-to-server (o que falta definir)

Verificado contra o repositório da API do jogo
(`BrookSK/project-survival-game`, RC1). Hoje a API concede itens via
`POST /admin/entitlements/grant` (RBAC: papel `SUPPORT`/`SUPER_ADMIN`, token de
admin) e tem um webhook **stub** (`POST /payments/webhooks/:provider`). **Não**
existem endpoints `/commerce/*` nem service account de escopo comercial.

Há duas alternativas (o site suporta ambas):

- **A (recomendada):** endpoints dedicados `POST /commerce/fulfillments`,
  `GET /commerce/fulfillments/:ref` e `POST /commerce/refunds`, com service
  account de escopo comercial e `Idempotency-Key`. É o que o adapter já consome.
- **B (reutiliza o existente):** um usuário de serviço com papel `SUPPORT`
  chamando `POST /admin/entitlements/grant`. Exige garantir idempotência no
  servidor (ou consultar `GET /player/entitlements` antes de conceder) e dá ao
  site um token de admin (escopo mais amplo).

Contrato completo (payloads, erros, idempotência e as duas alternativas) em
[`docs/api/commercial-integration.md`](api/commercial-integration.md).

## Autenticação server-to-server

O site usa uma service account (não o token do jogador) configurada no painel
(grupo `payments`): `game_api_service_client_id` e `game_api_service_secret`
(segredo mascarado). Enviada como `X-Client-Id` + `Authorization: Bearer`.

## Preço é sempre da API do jogo

`ProductPricing` relê o produto ao vivo para obter preço/moeda oficiais no
momento do pedido. Qualquer valor vindo do navegador é ignorado.

## Estado atual

Enquanto os endpoints comerciais da API do jogo não existirem/estiverem
configurados, os pedidos pagos ficam com entrega **pendente** (retry) e
aparecem na Reconciliação. Nada é concedido de forma fictícia.
