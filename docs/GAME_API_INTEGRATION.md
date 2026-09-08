# Integração com a Game API do Project Survival

Guia técnico de como o **site público** (PHP/MVC) consome a Game API oficial.
Baseado nos contratos do repositório do jogo (`BrookSK/project-survival-game`:
`docs/SITE_HANDOFF.md`, `docs/API_SITE_CONTRACT.md`, `docs/PUBLIC_DOWNLOAD.md`,
`docs/API_ENDPOINT_INVENTORY.md`).

## Arquitetura no site

```
Controller  →  Service (GameApi/…)  →  GameApiClient  →  HttpClient  →  Game API
```

- **`GameApiClient`**: única camada que conhece o transporte. Monta URL a partir
  da base configurada, injeta headers (`X-Client-Id` público, `Authorization:
  Bearer` quando há token do jogador), interpreta o envelope `{success,data}` e
  mapeia status HTTP → exceções. `get/post/put` devolvem `data`; `raw()` devolve
  o `HttpResponse` cru.
- **`HttpClient`**: único ponto com cURL. Timeout, retry só em GET, logs
  sanitizados (remove `authorization`, `cookie`, `x-client-id`).
- **`GameApiConfig`**: lê a configuração (grupos de settings `game_api` /
  `game_api_cache` / `releases`) e é a fábrica do client. Sem `.env`.
- **`UrlGuard`**: valida a base URL (anti-SSRF) e as URLs de download.

## Configuração (Admin, sem `.env`)

**Admin → Integrações → API do Jogo** e **Admin → Configurações**:

- `game_api`: `game_api_enabled`, `game_api_base_url` (inclui `/api/v1`),
  `game_api_client_id` (público), `game_api_timeout`, `game_api_version`,
  `game_api_maintenance`.
- `game_api_cache`: liga o cache e define TTLs por recurso.
- `releases`: `release_channel` (stable/beta/dev), `release_cache_ttl`,
  `download_allowed_hosts`.
- `website_urls`: URLs públicas (helper `game_url()`).

Segredos (quando existirem, ex.: service account comercial) são `is_secret`:
nunca exibidos e, se enviados vazios, mantêm o valor atual.

## Envelope e erros

- Sucesso: `{ "success": true, "data": … }`.
- Erro: `{ "success": false, "error": { "code", "message" } }`.
- Status → exceção: 400 Validation, 401/403 Authentication, 404 NotFound,
  409 Conflict, 429 RateLimit, 5xx/transporte ApiUnavailable.

## Endpoints consumidos

**Público:**
- `GET /health` (diagnóstico/admin e status com cache).
- `GET /public/releases/latest`, `GET /public/releases/{channel}`.
- `GET /public/download/{channel}` (usado pelo download; traz `download_url`).
- `GET /store/categories`, `GET /store/products`, `GET /store/products/:id`,
  `GET /store/catalog-meta`.
- `GET /news`, `GET /events`, `GET /game/config`.

**Auth/Player (mesma identidade do jogo):**
- `POST /auth/register|login|refresh|logout`, `GET /me`.
- `GET /player/entitlements` (**autoridade da posse**), `GET /player/inventory`.
- `POST /store/purchase` (inicia pedido `pending`; não cobra, não concede).

**Webhook (provedor → Game API):**
- `POST /payments/webhooks/:provider` — a **Game API** valida a assinatura,
  confirma o pedido e concede o entitlement (idempotente). O site não concede.

## Release / Download

`GameReleaseService` + `ReleaseInformation` consomem `/public/download/{channel}`
com cache (fresh + stale). O botão de download aponta para a rota permanente
`/download/project-survival`, que valida a URL e redireciona ao instalador
oficial. Ver [`docs/DOWNLOAD.md`](DOWNLOAD.md).

## Conta, produtos e entitlements

- Autenticação delega à Game API (sessão do jogador em `PlayerSession`,
  server-side, com refresh automático). O site não valida senha localmente nem
  guarda senha.
- Produtos são referenciados pelo **id da API** (catálogo não é duplicado).
- Entitlements são **exibidos**, nunca criados/editados pelo site.

## Cache

Cacheia apenas dados **públicos** (release, status, news, events, catálogo). Dados
privados (conta, entitlements, tokens) **nunca** são cacheados de forma
compartilhada — a API é a fonte de verdade.

## Resiliência

Se a API estiver indisponível, o site degrada com elegância: usa cache stale
quando existir e mostra mensagens amigáveis. A home e as páginas públicas não
quebram por causa de uma chamada externa lenta/falha.
