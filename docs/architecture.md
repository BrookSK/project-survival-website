# Arquitetura

O projeto segue o padrão **MVC** com uma implementação própria e leve, sem frameworks externos. Não há dependências de Composer para funcionar (autoloader PSR-4 próprio).

## Fluxo de uma requisição

```
Navegador
   │
   ▼
public/index.php  (Front Controller)
   │  1. carrega config/constants.php
   │  2. registra o Autoloader (App\ => app/)
   │  3. instancia App e chama boot() + run()
   ▼
App::boot()
   │  - carrega Config (config.php, database.php)
   │  - define timezone
   │  - configura tratamento de erros/exceções
   │  - envia headers de segurança
   │  - carrega helpers globais
   ▼
App::run()
   │  - inicia a sessão segura
   │  - se NÃO instalado  → carrega routes/install.php
   │  - se instalado      → compartilha dados globais + carrega web/admin/api
   ▼
Router::dispatch(Request)
   │  - casa a URI/método com uma rota
   │  - executa os middlewares (Auth, Guest, Permission, Maintenance)
   │  - invoca Controller@método
   ▼
Controller
   │  - valida entrada (Validator)
   │  - chama Services / Models
   │  - renderiza uma View com um layout
   ▼
Response  → HTML / JSON / redirect
```

## Camadas

```
Controller  →  Service  →  Model  →  Database (PDO)
```

- **Controllers** orquestram a requisição: validam, chamam serviços/models e escolhem a view. São finos.
- **Services** concentram regras de negócio e integrações (`AuthService`, `EmailService`, `UploadService`, `SettingsService`, `SeoService`, `AuditService`).
- **Models** encapsulam o acesso a dados, sempre com prepared statements. Estendem `Core\Model`.
- **Views** são templates PHP puros, renderizados por `Core\View`, normalmente dentro de um layout (`layouts/site` ou `layouts/admin`).

## Componentes do núcleo (`app/Core`)

| Componente   | Responsabilidade |
|--------------|------------------|
| `Autoloader` | Autoload PSR-4 do namespace `App\`. |
| `Config`     | Acesso à configuração por notação de ponto. |
| `Database`   | Singleton PDO + helpers de consulta. |
| `Model`      | CRUD base com whitelist de colunas. |
| `Router`     | Roteamento, grupos, parâmetros, middlewares. |
| `Request`    | Abstração da requisição HTTP. |
| `Response`   | Respostas + headers de segurança. |
| `View`       | Renderização de templates com layout. |
| `Session`    | Sessão segura + flash + old input. |
| `Csrf`       | Geração e validação de tokens CSRF. |
| `Logger`     | Log em arquivo (`storage/logs`). |
| `Migrator`   | Execução controlada de migrations/seeds. |
| `Controller` | Base para controllers. |
| `App`        | Bootstrap e orquestração. |

## Princípios

- Configuração centralizada, sem `.env` e sem credenciais no código.
- Segurança por padrão (CSRF, escaping, prepared statements, RBAC).
- Extensibilidade: novas permissões, páginas, rotas e serviços são adicionados sem reescrever o núcleo.
- Preparado para integrações futuras (Discord, Steam, newsletter, analytics) sem implementá-las prematuramente.

## Integração com a API do jogo (cliente HTTP)

Além do MVC do site, há uma camada dedicada de integração com a API oficial do
jogo em `app/Services/GameApi/`:

```text
Controller → Service (GameApi\*) → GameApiClient → HttpClient (cURL) → API /api/v1
```

- **HttpClient**: único ponto que faz cURL (timeout, retry seguro em GET, logs
  sanitizados).
- **GameApiClient**: interpreta o envelope `{success,data}`/`{success,error}` e
  mapeia status HTTP para exceções tipadas.
- **Services** (`GameAuthService`, `GameStoreService`, `GamePlayerService`,
  `GameContentService`, `GameConfigService`, `GameHealthService`): regras por
  domínio, cache das leituras públicas.
- **GameApiAdapter**: normaliza respostas para as views.
- **PlayerSession**: tokens do jogador na sessão server-side + refresh.
- **UrlGuard**: validação da URL base e proteção anti-SSRF.

O site é cliente da API; não acessa o banco do jogo. Detalhes em
[game-api.md](game-api.md).
