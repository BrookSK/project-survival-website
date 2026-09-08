# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue, de forma pragmática, o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)
e o versionamento adota [SemVer](https://semver.org/lang/pt-BR/).

## [2.3.0] - 2026-09-07

Camada comercial ponta a ponta no lado do site (PHP): checkout, pagamentos via
Mercado Pago, pedidos/transações/webhooks/cupons, painel administrativo,
reconciliação e reembolso. A **concessão de itens é sempre feita pela API do
jogo** (autoridade) — o site nunca concede/revoga entitlement diretamente e
nunca simula pagamento, entrega ou aprovação.

> **Nota de escopo:** o repositório da API do jogo (Node) não faz parte deste
> workspace. Os endpoints comerciais que o site consome
> (`/commerce/fulfillments`, `/commerce/refunds`) estão **especificados** em
> `docs/api/commercial-integration.md` e ainda **precisam ser implementados no
> lado da API do jogo**. Enquanto isso, pedidos pagos ficam com entrega
> pendente (com retry) e aparecem na Reconciliação — nada é concedido de forma
> fictícia.

### Adicionado

- **Migração `027_create_commerce_tables`**: `orders`, `order_items`,
  `payment_transactions`, `webhook_events`, `order_events` (timeline),
  `fulfillments`, `coupons`, `coupon_redemptions`. Dinheiro sempre em inteiro
  (centavos); estados separados para pedido, pagamento e entrega.
- **Abstração de gateway** (`app/Services/Payments`): `PaymentGatewayInterface`,
  `MercadoPagoGateway` (PIX e cartão via tokenização hospedada, consulta de
  pagamento, estorno, validação de assinatura de webhook HMAC-SHA256),
  `NullGateway` (recusa segura) e `PaymentService` (orquestra transações).
- **Adapter de concessão** (`GameFulfillmentService`): solicita a entrega à API
  do jogo de forma **idempotente** (`Idempotency-Key = referência:produto`);
  em indisponibilidade, mantém `pending` com backoff; nunca grava entitlement
  local. `ProductPricing` obtém preço/moeda oficiais da API (o navegador nunca
  define preço).
- **Checkout** (`/checkout`): cria o pedido com snapshot de preço antes do
  pagamento, valida produto/elegibilidade no servidor, aplica cupom validado no
  backend, cria o pagamento no gateway (PIX copia-e-cola/QR ou cartão) e exige
  aceite dos Termos de Compra. Chave de idempotência anti-duplo-clique.
- **Webhook** (`/webhooks/payment/{provider}`): fora de auth/CSRF, valida
  assinatura, é idempotente por `event_id`, **consulta o gateway para o estado
  real** (nunca confia no payload) e dispara a concessão idempotente.
- **Área do jogador**: `/conta/pedidos` e detalhe com timeline; estados
  refletem a confirmação real; proteção anti-IDOR por jogador da sessão.
- **Admin → Loja**: dashboard/saúde, pedidos (filtros/detalhe/timeline),
  transações, log de webhooks, fulfillments, **reconciliação** (pago sem
  entrega), reprocessar entrega, **reembolso** (permissão `store.refunds`, chama
  o gateway e solicita revogação à API do jogo conforme política) e **cupons**.
- **Configuração sem `.env`**: grupo `payments` (gateway, ambiente, moeda,
  chaves do Mercado Pago e service account da API do jogo — segredos mascarados)
  e permissões `store.*` (seeds 014/015).
- **Documentação**: `docs/api/commercial-integration.md`,
  `docs/commercial-flow.md`, `docs/payment-architecture.md`, `docs/fulfillment.md`,
  `docs/reconciliation.md`, `docs/game-api-integration.md` e
  `COMMERCIAL_AUDIT_REPORT.md`.

### Segurança

- Permissão `store.refunds` **não** é concedida por padrão (operação de maior
  risco). Refund nunca é só mudança de status local: chama o gateway.
- Segredos (access token, webhook secret, service secret) nunca são exibidos
  nem gravados em log; IDs externos aparecem, segredos não.
- Idempotência ponta a ponta: `webhook_events.event_id` e
  `fulfillments.idempotency_key` (UNIQUE). Teste crítico coberto: 10 webhooks
  idênticos + 3 retries resultam em 1 pedido, 1 pagamento lógico, 1 fulfillment
  e 1 entitlement.

## [2.2.0] - 2026-09-07

Camada de privacidade, LGPD, termos e governança de dados. Reflete o
comportamento real do sistema; nada de dados/empresa/provedores fictícios.

### Adicionado

- **Documentos legais versionados** (Política de Privacidade, Termos de Uso,
  Termos de Compra, Política de Reembolso, Política de Cookies): CRUD no admin
  com versões imutáveis (rascunho/publicado/arquivado), data de vigência,
  registro de responsável e histórico. Páginas públicas em `/privacidade`,
  `/termos`, `/termos-de-compra`, `/reembolso`, `/cookies`.
- **Central de Privacidade do jogador** (`/conta/privacidade`): ver dados,
  gerenciar consentimento de marketing, **exportar dados** (JSON sem
  senhas/tokens, arquivo privado com expiração) e **solicitar exclusão**.
- **Consentimento no cadastro**: aceite obrigatório de Termos + Privacidade
  (com versão) separado de marketing (opt-in); banner de reaceite quando uma
  nova versão obrigatória é publicada.
- **Admin → Privacidade**: dashboard, solicitações de titulares (fluxo de
  status com verificação de identidade e auditoria), visão de consentimentos e
  exportações, configurações (dados da empresa/contato/retenção).
- **Cookies**: banner Aceitar/Recusar/Configurar; página de preferências; só
  categorias reais (essenciais; não há trackers de terceiros hoje).
- **Documentação**: `docs/privacy.md`, `data-map.md`, `consent-management.md`,
  `data-retention.md`, `data-subject-requests.md`, `cookies.md`,
  `security-incidents.md` e `PRIVACY_AUDIT_REPORT.md`.

### Segurança

- Permissões `privacy.*` segregadas (exclusão/exportação exigem concessão
  explícita; editor de conteúdo não as recebe).
- Exportações fora de `/public`, com token (hash) e expiração; download só do
  próprio titular autenticado. Máscara de e-mail no painel. `noindex` em áreas
  privadas. Auditoria registra a ação, não os dados pessoais.

### Banco de dados

- Migration nova **026** (`privacy_policies`, `privacy_policy_versions`,
  `privacy_consents`, `privacy_requests`, `data_exports`) e seeds **012**
  (permissões) e **013** (settings de privacidade + registro dos documentos).
  Nenhuma migration existente foi alterada. Contas de jogador continuam na
  Game API — o site não as replica.

## [2.1.0] - 2026-09-07

Integração oficial do website com a **Project Survival API** (`/api/v1`). O site
passa a atuar como cliente server-side da API do jogo, sem tocar no banco do jogo
e sem alterar migrations existentes.

### Adicionado

- **Camada de integração** em `app/Services/GameApi/`: `HttpClient` (cURL com
  timeout, retry seguro em GET, logs sanitizados), `GameApiClient` (envelope
  `{success,data}`/`{success,error}` + mapeamento de status para exceções),
  `GameApiConfig` (fábrica), `GameApiAdapter` (normalização) e exceções
  tipadas (ApiUnavailable/Authentication/Validation/NotFound/Conflict/RateLimit).
- **Services**: `GameAuthService`, `GameStoreService`, `GamePlayerService`,
  `GameContentService`, `GameConfigService`, `GameHealthService`.
- **Autenticação do jogador** contra a API: páginas `/login`, `/criar-conta` e
  `/logout`. Tokens guardados apenas em sessão server-side; refresh automático
  (uma vez) em 401 via `PlayerSession::withAuth()`.
- **Área do jogador** (`/conta`, `/conta/inventario`, `/conta/resgatar`,
  `/conta/seguranca`) protegida por `PlayerAuthMiddleware`.
- **Loja pública** (`/loja`, `/loja/produto/{id}`) com `owned` por jogador
  quando autenticado; a compra apenas inicia um pedido `pending` (sem cobrança,
  sem concessão, sem checkout falso).
- **Eventos** (`/eventos`) e enriquecimento da **Home** com notícias, eventos e
  produtos em destaque da API (aditivo e tolerante a falhas).
- **Admin → Integrações → API do Jogo**: status/health, testar conexão, salvar
  conexão (com validação anti-SSRF) e limpar cache; abas de configuração em
  Configurações (API do Jogo / Cache da API).
- **Cache offline-first** (fresh + stale) para leituras públicas; dados privados
  do jogador nunca são cacheados de forma compartilhada.
- **Testes de unidade** da integração (cliente/adapter/UrlGuard/erros) via
  `MockHttpClient`, sem rede.
- Documentação `docs/game-api.md` com a matriz de endpoints.

### Segurança

- Proteção anti-SSRF na URL base configurável (`UrlGuard`).
- Tokens do jogador fora do alcance do JavaScript; cabeçalhos sensíveis nunca
  logados; CSRF em todos os formulários.

### Banco de dados

- Nenhuma migration nova e nenhuma migration existente alterada. A configuração
  da integração usa a tabela `settings` via o seed idempotente
  `011_game_api_settings.sql`. O banco da API do jogo **não** é acessado pelo site.

## [2.0.0] - 2026-09-07

Evolução completa do website para um produto pronto para produção: CMS ampliado,
biblioteca de mídia, segurança reforçada, cache, i18n preparado e documentação.

### Adicionado

- **Home modular**: seções editáveis por tipo (conteúdo, features, screenshots,
  trailer, notícias, FAQ, CTA) com ordenação por arrastar e soltar.
- **Banners desktop/mobile**: imagem específica para telas menores (`<picture>`).
- **Notícias (CMS completo)**: agendamento de publicação, destaque com limite
  configurável, soft delete (lixeira), filtros, ordenação, ações em lote e
  categorias com imagem e status.
- **Biblioteca de mídia**: upload múltiplo, geração de variantes
  (thumbnail/medium/large) e conversão para WebP quando o GD está disponível,
  metadados (título, texto alternativo).
- **Vídeos/Trailers**: cadastro por URL (YouTube/Vimeo/externo) com detecção
  automática de provedor, thumbnail e ID; página pública de vídeos.
- **Galeria profissional**: lightbox no site, carregamento preguiçoso e endpoint
  de reordenação de itens.
- **Redes sociais e rodapé configuráveis**, logo/favicon, verificação do Google,
  texto de copyright e **banner de cookies (LGPD)**.
- **Templates de e-mail** editáveis com placeholders e layout HTML responsivo.
- **Notificações administrativas** (sino com contador) para eventos como novas
  mensagens de contato.
- **Dashboard evoluído** com atalhos, métricas e mini-gráfico de notícias.
- **Sistema e Diagnóstico**: informações do ambiente, execução de migrations pelo
  painel e health check (extensões, permissões, banco, SMTP).
- **Redirects 301/302** gerenciáveis com contagem de acessos.
- **Cache de dados** por arquivo (configurações, menus, sitemap) com limpeza pelo
  painel.
- **i18n preparado**: serviço de tradução, helper `__()` e dicionários
  `resources/lang/` (pt-BR base, en de exemplo).
- **API pública somente-leitura**: `/api/health` e `/api/news`.
- **Suíte de testes de unidade** sem dependências externas (`php tests/run.php`).
- **Acessibilidade**: skip link, foco visível, respeito a `prefers-reduced-motion`
  e estados de carregamento em botões.

### Alterado / Refatorado

- `EmailService` passa a suportar templates com placeholders e um wrapper HTML.
- `Model` ganhou suporte a **soft delete** (`deleted_at`) e restauração.
- `Database` ganhou wrapper de **transação** (`transaction(callable)`), usado em
  operações multi-etapa (ex.: notícia + categorias).
- Timezone e locale centralizados a partir das configurações administráveis.
- Cabeçalhos de segurança reforçados (CSP, Permissions-Policy, COOP).

### Segurança

- **Rate limiting** por arquivo para contato e recuperação de senha.
- **Sanitização de HTML** (whitelist) para todo conteúdo rico salvo pelo editor.
- Bloqueio explícito de extensões executáveis em uploads (defesa em profundidade).
- Logs de segurança para limites de taxa e uploads recusados.

### Banco de dados

- Novas migrations **015–025** (imutáveis): `image_mobile` em banners,
  `home_sections`, evolução de `news`/`news_categories`/`pages`, `media`,
  `videos`, `social_links`, `email_templates`, `admin_notifications`, `redirects`.
- Novos seeds idempotentes (permissões v2, configurações v2, seções da home,
  templates de e-mail, páginas legais).

> Nenhuma migration existente (001–014) foi alterada. Toda evolução de schema
> foi feita com novas migrations, conforme a regra de imutabilidade.

## [1.0.0] - 2025

- Versão inicial: site público, painel administrativo, CMS de páginas/notícias,
  galeria, FAQ, contato, e-mail configurável, SEO, RBAC, auditoria e instalador.
