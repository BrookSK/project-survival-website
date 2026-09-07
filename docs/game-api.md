# Integração com a Project Survival API

Este documento descreve como o website (PHP/MVC) se integra à **API oficial do
Project Survival** (Node + Express + TypeScript). O website atua como **cliente
server-side** da API — nunca acessa o banco do jogo diretamente.

Contrato oficial (fonte de verdade): `docs/api/API.md` e `docs/api/openapi.yaml`
no repositório do jogo. Em caso de dúvida sobre endpoint, campo, status ou
regra de negócio, o contrato da API prevalece.

## Arquitetura

```text
Navegador
    │
    ▼
Website PHP (MVC)
    │  Controllers → Services (GameApi\*) → GameApiClient → HttpClient (cURL)
    ▼
Project Survival API  (/api/v1)
    ▼
Backend do jogo
```

Camadas (em `app/Services/GameApi/`):

- **HttpClient** — único ponto que executa cURL. Timeout, headers, JSON,
  captura de latência/`x-request-id`, logs sanitizados e retry seguro (apenas
  GET, uma repetição em erro de transporte).
- **GameApiClient** — monta URL/headers, interpreta o envelope
  `{ success, data }` / `{ success, error }` e mapeia o status HTTP para exceções.
- **Services especializados** — `GameAuthService`, `GameStoreService`,
  `GamePlayerService`, `GameContentService`, `GameConfigService`,
  `GameHealthService`.
- **GameApiAdapter** — normaliza o JSON bruto para as views sem inventar campos.
- **GameApiConfig** — fábrica central que lê a configuração administrável e cria
  o client.
- **PlayerSession** — guarda os tokens do jogador na sessão server-side e faz o
  fluxo de refresh.
- **UrlGuard** — valida a URL base e protege contra SSRF.

## Base URL

Configurável no painel (sem `.env`), em **Admin → Integrações → API do Jogo** ou
**Admin → Configurações → API do Jogo**. Padrão de desenvolvimento:

```text
http://localhost:4000/api/v1
```

Inclua sempre o caminho `/api/v1`. Em produção, a URL deve usar **HTTPS**.

## Configuração (tabela `settings`, sem `.env`)

Grupo `game_api`:

| Chave | Descrição | Padrão |
| ----- | --------- | ------ |
| `game_api_enabled` | Ativa a integração | `0` |
| `game_api_base_url` | URL base da API | `http://localhost:4000/api/v1` |
| `game_api_client_id` | Client ID público (não é segredo) | vazio |
| `game_api_timeout` | Timeout em segundos (1–30) | `8` |

Grupo `game_api_cache`: `game_api_cache_enabled` e os TTLs `..._ttl_news`,
`..._ttl_events`, `..._ttl_store`, `..._ttl_categories`, `..._ttl_config`.

> As configurações são criadas pelo seed idempotente
> `database/seeds/011_game_api_settings.sql`. Após atualizar o código, rode as
> migrations/seeds em **Admin → Sistema → Executar migrations**.

## Autenticação do jogador

A conta do jogador pertence à API. O site apenas coleta credenciais, chama o
endpoint oficial e guarda os tokens na **sessão server-side** (cookie
HttpOnly/SameSite). Os tokens **nunca** vão para o HTML, JavaScript, URLs ou logs.

- **Access token (JWT)**: enviado em `Authorization: Bearer <token>`.
- **Refresh token**: usado apenas server-to-server para renovar o acesso.

### Fluxo de refresh

```text
operação autenticada → 401
        ↓
refresh (uma vez) com { user_id, refresh_token }
        ↓ sucesso                    ↓ falha
repete a operação           encerra a sessão local → /login
```

Implementado em `PlayerSession::withAuth()`: no máximo **uma** tentativa de
refresh por operação (sem loop). Um 403 (sem permissão) não dispara refresh.

## Matriz de endpoints

| Endpoint | Uso no site | Auth |
| -------- | ----------- | ---- |
| `GET /health` | Diagnóstico/status (admin) | não |
| `POST /auth/register` | Cadastro (`/criar-conta`) | não |
| `POST /auth/login` | Login (`/login`) | não |
| `POST /auth/refresh` | Renovação de tokens | refresh |
| `POST /auth/logout` | Logout (`/logout`) | sim |
| `POST /auth/change-password` | Segurança (`/conta/seguranca`) | sim |
| `GET /me` | Perfil (`/conta`) | sim |
| `GET /store/categories` | Loja (`/loja`) | não |
| `GET /store/products` | Loja (traz `owned` se autenticado) | opcional |
| `GET /store/products/:id` | Detalhe (`/loja/produto/{id}`) | não |
| `POST /store/purchase` | Iniciar pedido (`/loja/comprar`) | sim |
| `GET /player/entitlements` | Itens possuídos (`/conta/inventario`) | sim |
| `GET /player/inventory` | Inventário/pedidos | sim |
| `POST /redeem` | Resgatar código (`/conta/resgatar`) | sim |
| `GET /news` | Notícias do jogo (Home) | não |
| `GET /events` | Eventos (`/eventos`, Home) | não |
| `GET /game/config` | Config/flags/versão | não |

Esta matriz deve permanecer alinhada ao contrato oficial da API.

## Cache (offline-first)

Leituras públicas (notícias, eventos, loja, categorias, config) usam o
`CacheService` (arquivo) com duas cópias por recurso:

- **fresh** — com TTL configurável;
- **stale** — persistente, servida como fallback quando a API está indisponível.

Dados privados do jogador (`/me`, entitlements, inventário, `owned`) **nunca**
são cacheados de forma compartilhada — a API é a fonte de verdade.

Limpeza pelo painel em **Integrações → Cache da API** (tudo/notícias/eventos/
loja/config) ou programaticamente via `flushCache()` dos serviços.

## Tratamento de erros

O `GameApiClient` converte status em exceções (`app/Services/GameApi/Exceptions`):

| Situação | Exceção |
| -------- | ------- |
| Timeout/conexão/5xx | `ApiUnavailableException` |
| 400 | `ValidationException` |
| 401/403 | `AuthenticationException` |
| 404 | `NotFoundException` |
| 409 | `ConflictException` |
| 429 | `RateLimitException` (com `retryAfter`) |
| JSON inválido | `GameApiException` |

O `ErrorPresenter` traduz exceções em mensagens amigáveis, sem expor stack
traces, tokens ou detalhes internos.

## Segurança

- Tokens só na sessão server-side; jamais em HTML/JS/URL/logs.
- CSRF em todos os formulários (login, registro, resgate, compra, senha, logout).
- **Anti-SSRF** na URL base (`UrlGuard`): protocolo http/https; em produção,
  HTTPS obrigatório e bloqueio de loopback/rede privada. Em dev, localhost é
  permitido.
- Cabeçalhos sensíveis (`Authorization`, `Cookie`, `X-Client-Id`) nunca são
  logados.
- Comunicação server-to-server (não requer CORS amplo para o site).
- A senha do jogador nunca é armazenada no banco do site.

## Desenvolvimento local

1. Suba a API do jogo: `cd api && npm install && npm run seed && npm run dev`
   (porta 4000).
2. Suba o website PHP apontando o DocumentRoot para `public/`.
3. Em **Admin → Integrações → API do Jogo**, ative a integração e confirme a
   Base URL `http://localhost:4000/api/v1`. Clique em **Testar conexão**.

## Troubleshooting

- **"Integração desativada"**: ative em Integrações ou em Configurações → API do
  Jogo (`game_api_enabled`).
- **Loja/notícias vazias e status Offline**: verifique se a API está no ar e a
  Base URL/timeout. Em produção, a URL precisa ser HTTPS e não pode apontar para
  endereços privados.
- **Configurações da integração não aparecem**: rode as migrations/seeds em
  Admin → Sistema (o seed `011` cria as chaves).
- **Sessão do jogador cai ao navegar**: o refresh falhou; o jogador é levado ao
  login. Verifique o relógio do servidor e a validade do refresh token.
- **Dados desatualizados**: limpe o cache em Integrações → Cache da API.

## O que a integração NÃO faz

- Não acessa o banco do jogo diretamente.
- Não copia contas, produtos, entitlements ou pedidos para o banco do site.
- Não simula pagamento nem concede itens (a compra apenas inicia um pedido
  `pending`).
- Não inventa endpoints, campos, eventos expirados ou versões.
