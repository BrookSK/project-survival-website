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

## O que a API do jogo precisa implementar (contrato)

Estes endpoints **ainda não existem** e são consumidos pelo adapter do site
(mockados nos testes):

- `POST /commerce/fulfillments` — concede os itens de um pedido pago
  (idempotente por `Idempotency-Key`).
- `GET /commerce/fulfillments/:order_reference` — consulta a concessão.
- `POST /commerce/refunds` — revoga/estorna a concessão (política revoke/keep).
- Autenticação de **service account** (client/secret dedicados ao site) com
  escopo comercial.

Consulte o contrato completo (payloads, códigos de erro, idempotência) em
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
