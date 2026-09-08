# Site Handoff — integração deste site com o Project Survival

Guia rápido para quem for manter o **site público** (este repositório) entender a
integração com a plataforma do jogo sem ler todo o código. Detalhes completos em
[`docs/GAME_API_INTEGRATION.md`](GAME_API_INTEGRATION.md),
[`docs/DOWNLOAD.md`](DOWNLOAD.md) e [`docs/SITE_GAME_CONTRACT.md`](SITE_GAME_CONTRACT.md).

Este projeto é o **site público do Project Survival**. Ele consome a Game API
oficial (repositório separado do jogo) e não duplica contas, catálogo,
entitlements nem releases.

1. **Game API Base URL** — configurável em Admin → Integrações → API do Jogo
   (grupo `game_api`). Inclui `/api/v1`. Sem `.env`.
2. **Release endpoint** — `GET /public/releases/{channel}` (stable/beta/dev).
3. **Download endpoint** — `GET /public/download/{channel}` (traz `download_url`).
   Página `/download`; rota permanente `/download/project-survival`.
4. **Auth** — `POST /auth/register|login|refresh|logout`, `GET /me`. Mesma
   identidade do jogo. Sessão do jogador server-side (`PlayerSession`).
5. **Account** — área `/conta` exibe perfil, inventário, pedidos, entitlements.
6. **Products** — `GET /store/products{/:id}`, `/store/categories`,
   `/store/catalog-meta`. Referência por id da API.
7. **Entitlements** — `GET /player/entitlements` (autoridade da posse). O site só
   exibe.
8. **Fulfillment** — modo oficial `game_webhook`: a Game API concede via webhook
   do provedor. O site não concede (`GameFulfillmentInterface` /
   `NullFulfillmentAdapter`). Ver `docs/api/commercial-integration.md`.
9. **Payment integration** — camada comercial do site (`PaymentGatewayInterface`,
   checkout, `orders`/`payment_transactions`). Ver `COMMERCIAL_AUDIT_REPORT.md`.
10. **Webhooks** — `POST /payments/webhooks/:provider` é processado pela Game API
    (assinatura + idempotência). O site orquestra o checkout; não confirma
    pagamento sozinho.
11. **URLs** — grupo `website_urls` + helper `game_url()` (download/store/account/
    support/privacy/terms/website). Nada hardcoded.
12. **Security** — HTTPS em produção; anti-SSRF/open-redirect no download
    (`UrlGuard::isSafeDownloadUrl`); segredos nunca no frontend/log; origem do
    site em `CORS_ORIGINS` da API.
13. **Cache** — só dados públicos (release/status/news/events/catálogo);
    privados nunca. TTLs configuráveis; fallback stale offline-first.
14. **Errors** — envelope `{success,error:{code,message}}`; mapeado para exceções
    no `GameApiClient`.
15. **Versioning** — `/api/v1`; header de resposta `X-API-Version`.

## Configuração mínima para operar

- Admin → Integrações → API do Jogo: ativar, definir Base URL (HTTPS em prod),
  Client ID, timeout; **Testar conexão**.
- Admin → Configurações → Releases / Download: canal e cache.
- Admin → Configurações → URLs do site: combinar domínios com a equipe do jogo.
- Garantir que a origem do site esteja em `CORS_ORIGINS` da Game API.

## Onde olhar no código

- Releases/download: `app/Services/GameApi/GameReleaseService.php`,
  `ReleaseInformation.php`, `app/Controllers/Site/DownloadController.php`.
- Status/updates: `GameStatusService.php`, `app/Controllers/Site/UpdatesController.php`.
- Cliente/So config: `GameApiClient.php`, `GameApiConfig.php`, `UrlGuard.php`.
- Comercial: `app/Services/Payments/*`, `app/Services/Commerce/*`.
- Admin: `app/Controllers/Admin/IntegrationController.php`.
