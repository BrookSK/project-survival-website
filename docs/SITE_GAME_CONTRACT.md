# Contrato Site ⇄ Project Survival

Divisão de responsabilidades entre o **site público** (este repositório) e a
plataforma do jogo. Uma só fonte de verdade para cada coisa — sem duplicação.

```
                 SITE  ⇄  GAME API  ⇄  GAME DATABASE
                              │
        RELEASE SERVER / STORAGE  →  INSTALLER  →  LAUNCHER  →  UPDATER  →  GAME
```

## SITE (este repositório)

Responsável por:

- Frontend, conteúdo (CMS), experiência e SEO.
- Página e UI de **download** (consome a release oficial; não hospeda o binário).
- **Checkout e pagamentos** (camada comercial): monta o pedido, fala com o
  provedor de pagamento e registra `order`/`payment`/timeline no banco do site.
- Consumir e **exibir** conta, produtos e entitlements (nunca é autoridade sobre
  eles).

O site **não**: mantém contas de jogador próprias, duplica catálogo/entitlements,
publica releases, concede itens, nem confia no navegador para preço/status.

## GAME API

Autoridade sobre:

- Contas e autenticação (mesma identidade do jogo).
- Produtos oficiais, categorias, taxonomia.
- **Entitlements/posse** e inventário do jogador.
- Pedidos e **concessão** (via webhook do provedor de pagamento).
- Metadados de release/download (`/public/releases/*`, `/public/download/*`).

## RELEASE SERVER / STORAGE

Responsável por: instalador, `game.zip`, launcher, manifesto do canal, hashes
(SHA-256) e distribuição (storage/CDN). O site apenas aponta para as URLs que a
API informa.

## LAUNCHER / UPDATER

Responsáveis por: verificação de atualização, download do update, validação
(SHA-256) e aplicação. O site **não** implementa isso.

## GAME

Responsável por: gameplay, cliente, consumo de entitlements, funcionamento
offline-first.

## Fonte de verdade (resumo)

| Dado | Autoridade |
| ---- | ---------- |
| Conta / autenticação | Game API |
| Produtos / catálogo | Game API |
| Entitlements / posse | Game API |
| Concessão de item | Game API (via webhook do provedor) |
| Release / versão / installer / SHA-256 | Release server (exposto pela API em `/public/*`) |
| Pedido comercial / pagamento / transação | Site (banco do site) |
| Preço final cobrado | Sistema comercial/pagamento do site |
| Conteúdo do site (CMS), SEO, download UI | Site |

## Fluxo de compra (oficial)

```
SITE → CHECKOUT → PROVEDOR DE PAGAMENTO → WEBHOOK → GAME API → ORDER → ENTITLEMENT → GAME
```

O site inicia o pedido e envia ao provedor. Quando o pagamento confirma, o
**provedor** chama o webhook da **Game API**, que valida a assinatura, confirma o
pedido e concede o entitlement (idempotente). O site **não** concede posse nem
confirma pagamento sozinho; confirma a posse consultando `GET /player/entitlements`.

Detalhes de contrato: [`docs/api/commercial-integration.md`](api/commercial-integration.md),
[`docs/game-api-integration.md`](game-api-integration.md),
[`docs/GAME_API_INTEGRATION.md`](GAME_API_INTEGRATION.md).
