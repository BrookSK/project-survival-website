# Game API Handoff — o que o site precisa e o que o jogo fornece

Documento de handoff do ponto de vista do **site**. Lista o que o site consome
da Game API e o que a equipe do jogo precisa garantir/fornecer. Contrato base:
`BrookSK/project-survival-game` (`docs/SITE_HANDOFF.md`, `API_SITE_CONTRACT.md`,
`openapi.yaml`).

## O site precisa de:

- **API Base URL** (`/api/v1`), configurável no painel (sem `.env`).
- **OpenAPI** (`docs/api/openapi.yaml`) como fonte do contrato.
- **Autenticação**: `POST /auth/register|login|refresh|logout`, `GET /me`.
- **Releases/Download**: `GET /public/releases/latest`,
  `GET /public/releases/{channel}`, `GET /public/download/{channel}` — com
  `version`, `platform`, `requirements`, `installer{ url, permanent_url, sha256, size }`,
  `download_url`.
- **Produtos**: `GET /store/products`, `/store/products/:id`, `/store/categories`,
  `/store/catalog-meta` (ids estáveis; `owned` quando autenticado).
- **Entitlements**: `GET /player/entitlements` (autoridade da posse).
- **Compra**: `POST /store/purchase` (inicia pedido `pending`).
- **Webhook de pagamento**: `POST /payments/webhooks/:provider` (a Game API
  concede o entitlement, idempotente).
- **Erros**: envelope `{success,error:{code,message}}` com códigos estáveis.
- **Rate limits** documentados (público 60/min, etc.).

## A equipe do jogo precisa fornecer/garantir:

- **URL da API** de produção (HTTPS) e inclusão da **origem do site** em
  `CORS_ORIGINS`.
- **Contrato estável** com versionamento (`/api/v1`; header `X-API-Version`).
- **Schemas e códigos de erro** consistentes (openapi atualizado).
- **Release publicada** no canal público (`stable`) com `installer.permanent_url`
  (ou `download_url`) estável, `sha256` e `size` corretos.
- **Domínios** combinados para as URLs configuráveis (website/store/support/
  privacy/terms/account/download).
- **Webhook** do provedor de pagamento apontando para a Game API, com validação
  de assinatura e idempotência (`order_id` + `provider_transaction_id`).

## Pendências/decisões em aberto

- **Concessão comercial**: o modelo oficial é `game_webhook` (a Game API concede
  no webhook do provedor). O site suporta também um modo `commerce_endpoint`
  (endpoint dedicado de concessão), que **só** deve ser ativado se a Game API
  expuser tal endpoint com contrato oficial. Ver
  [`docs/api/commercial-integration.md`](api/commercial-integration.md).
- **Correlation ID**: se a Game API adotar um header padrão de correlação para
  rastreio ponta a ponta, informar o nome para o site passar a enviá-lo.

## Configuração no site (resumo)

Tudo no painel (**Admin → Integrações** e **Configurações**), sem `.env`:
base URL, client id (público), timeout, canal de release, TTLs de cache, URLs do
site e allowlist de hosts de download. Segredos ficam mascarados.
