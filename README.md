# Website Oficial do Jogo

Website oficial de um jogo, desenvolvido em **PHP puro com arquitetura MVC própria**, sem frameworks pesados. Inclui site público com identidade visual de jogo, painel administrativo completo, CMS modular, notícias com agendamento/destaque, biblioteca de mídia com otimização de imagens, vídeos, galeria com lightbox, FAQ, contato, e-mail com templates, SEO, redirects, cache, controle de acesso por permissões, auditoria, notificações e um instalador guiado.

> Este repositório contém **apenas o website**. O jogo em si é mantido em um repositório separado.

**Versão atual: 2.0.0** — veja o [CHANGELOG](CHANGELOG.md).

---

## Sumário

- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração (sem .env)](#configuração-sem-env)
- [Banco de dados](#banco-de-dados)
- [Migrations](#migrations)
- [Estrutura de diretórios](#estrutura-de-diretórios)
- [Rotas principais](#rotas-principais)
- [Área administrativa](#área-administrativa)
- [E-mail (SMTP)](#e-mail-smtp)
- [Uploads](#uploads)
- [Segurança](#segurança)
- [Desenvolvimento](#desenvolvimento)
- [Produção](#produção)
- [Backup](#backup)
- [Documentação adicional](#documentação-adicional)

---

## Requisitos

- PHP **>= 8.0** (recomendado 8.1+)
- MySQL **>= 5.7** ou MariaDB **>= 10.3**
- Servidor web Apache (com `mod_rewrite`) ou Nginx
- Extensões PHP obrigatórias:
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `fileinfo`
  - `json`
- Extensões recomendadas:
  - `gd` — otimização de imagens e geração de WebP (a mídia degrada sem ela)
  - `curl`, `zip`

---

## Instalação

1. Envie os arquivos do projeto para o servidor.
2. Aponte o **DocumentRoot** para a pasta `public/` (recomendado) ou para a raiz do projeto (o `.htaccess` da raiz redireciona para `public/`).
3. Garanta permissão de escrita para:
   - `config/` (o instalador grava `config/local.php`)
   - `storage/` e subpastas
   - `public/uploads/`
4. Acesse o site no navegador. Como o sistema ainda não está instalado, você será levado ao **instalador** em `/install`.
5. O instalador verifica os requisitos, solicita os dados do **banco de dados**, do **administrador inicial** e do **site**, e então:
   - gera `config/local.php` (com uma chave de aplicação aleatória),
   - executa todas as migrations e seeds,
   - cria o usuário administrador (perfil Super Administrador),
   - grava `config/installed.lock` para bloquear reinstalações.
6. Ao final, acesse `/admin/login` com as credenciais definidas.

> Para reinstalar, remova manualmente `config/installed.lock` (ação administrativa explícita).

---

## Configuração (sem .env)

Este projeto **não usa `.env`**. A configuração é dividida em duas camadas:

### 1. Configuração de infraestrutura (arquivos)

- `config/constants.php` — caminhos e constantes globais.
- `config/config.php` — configurações não sensíveis da aplicação (sessão, segurança, uploads, logs).
- `config/database.php` — normaliza os dados de conexão.
- `config/local.php` — **gerado na instalação**, contém credenciais (banco, `app_key`). **Nunca é versionado** (está no `.gitignore`). Use `config/local.php.example` como referência.

Nenhuma credencial fica espalhada pelo código. Tudo o que é sensível vive em `config/local.php`.

### 2. Configuração administrável (banco de dados)

Configurações que o administrador altera pelo painel ficam na tabela `settings`, organizadas por grupo (Gerais, E-mail, SEO, Redes sociais, Sistema) e acessíveis em **Administração → Configurações**.

---

## Banco de dados

O banco é totalmente documentado por arquivos `.sql`:

```
database/
├── schema/      # Retrato consolidado do schema (referência de leitura)
├── migrations/  # Fonte de execução (numeradas, IMUTÁVEIS)
└── seeds/        # Dados iniciais (idempotentes)
```

Todas as tabelas usam InnoDB e `utf8mb4_unicode_ci`, com chaves primárias, estrangeiras, índices e restrições `UNIQUE` adequadas.

---

## Migrations

O sistema possui um mecanismo próprio de migrations (`app/Core/Migrator.php`) e uma tabela de controle `migrations`.

### Regra crítica (imutabilidade)

> **NUNCA edite um arquivo de migration já criado.**

Depois de versionada, uma migration é considerada imutável. Para qualquer alteração de schema (adicionar/alterar/remover coluna, índice, tabela, relacionamento), **crie uma nova migration**:

```
001_create_users_table.sql        (existente — não alterar)
015_add_phone_to_users.sql        (nova migration com apenas a alteração)
```

A migration deve conter **somente** a alteração necessária. O sistema identifica migrations executadas e pendentes e nunca roda a mesma duas vezes.

Durante a instalação, todas as migrations pendentes e os seeds são executados automaticamente.

---

## Estrutura de diretórios

```
/
├── app/
│   ├── Controllers/     # Controllers (Admin/, Site/, InstallController)
│   ├── Core/            # Núcleo MVC (Router, Database, Model, View, etc.)
│   ├── Helpers/         # Funções auxiliares globais
│   ├── Middleware/      # Auth, Guest, Permission, Maintenance
│   ├── Models/          # Camada de dados (PDO)
│   ├── Services/        # Regras de negócio (Auth, Email, Upload, SEO, etc.)
│   ├── Validators/      # Validação reutilizável
│   └── Views/           # Templates (admin/, site/, layouts/, errors/, install/)
│
├── config/              # Configuração (sem .env)
├── database/            # schema/, migrations/, seeds/
├── public/              # Raiz pública (front controller + assets + uploads)
│   ├── index.php
│   └── assets/{css,js,images,fonts}
├── routes/              # web.php, admin.php, api.php, install.php
├── storage/             # logs/, cache/, uploads/ (privados), backups/
├── docs/                # Documentação técnica
├── index.php            # Delega para public/index.php (quando DocumentRoot = raiz)
├── .htaccess            # Reescrita para o front controller
└── README.md
```

---

## Rotas principais

### Site público
- `/` — Home
- `/sobre`, `/gameplay` — páginas de conteúdo
- `/noticias`, `/noticias/{slug}` — notícias
- `/galeria`, `/galeria/{slug}` — galeria
- `/faq` — perguntas frequentes
- `/contato` — formulário de contato
- `/p/{slug}` — páginas dinâmicas adicionais
- `/sitemap.xml`, `/robots.txt` — SEO técnico

### Administração (`/admin`, requer login)
- `/admin` — dashboard
- `/admin/home` — seções modulares da home
- `/admin/paginas`, `/admin/noticias`, `/admin/categorias`, `/admin/faq`, `/admin/galeria`, `/admin/videos`, `/admin/midia`, `/admin/banners`, `/admin/menus`
- `/admin/mensagens` — mensagens de contato
- `/admin/notificacoes` — notificações administrativas
- `/admin/redes-sociais`, `/admin/email-templates`, `/admin/redirects`
- `/admin/usuarios`, `/admin/perfis` — usuários e permissões
- `/admin/configuracoes` — configurações do sistema
- `/admin/auditoria` — logs de auditoria
- `/admin/sistema`, `/admin/diagnostico` — informações, migrations, health check

### API pública (somente leitura, JSON)
- `/api/health` — status e versão da aplicação
- `/api/news?limit=6` — notícias publicadas recentes

---

## Área administrativa

Acesse `/admin/login`. O controle de acesso é baseado em **perfis (roles)** e **permissões** granulares (ex.: `news.create`, `users.delete`).

Perfis padrão:
- **Super Administrador** — acesso total e irrestrito.
- **Administrador** — acesso amplo (exceto gestão de perfis).
- **Editor** — gerencia conteúdo.

Novos perfis e permissões podem ser criados pelo painel.

---

## E-mail (SMTP)

A configuração de e-mail é feita em **Configurações → E-mail** e armazenada na tabela `settings`:

- Host, porta, usuário, senha, criptografia (TLS/SSL/none)
- Nome e e-mail do remetente, reply-to

O envio é centralizado no `EmailService` (cliente SMTP nativo, sem dependências externas). Há um botão **"Enviar e-mail de teste"** para validar a configuração. O formulário de contato usa este serviço para notificar o administrador.

---

## Uploads

- Formatos aceitos: JPG, JPEG, PNG, WEBP, GIF.
- Validação por **MIME real** (não apenas extensão), tamanho máximo e verificação de imagem.
- Nomes de arquivo são aleatórios; o nome original nunca é preservado.
- Uploads públicos ficam em `public/uploads/` (com `.htaccess` que **impede execução de scripts**).
- `storage/uploads/` é reservado para arquivos privados.

---

## Segurança

- Senhas com `password_hash()` (bcrypt, custo 12).
- Proteção **CSRF** em todas as requisições de escrita.
- Proteção contra **brute force** no login (bloqueio temporário por tentativas).
- **PDO com prepared statements** em toda a camada de dados.
- **Escape de saída** (`e()`) nas views para prevenir XSS.
- Headers de segurança: **Content-Security-Policy**, **Permissions-Policy**, Cross-Origin-Opener-Policy, X-Content-Type-Options, X-Frame-Options, Referrer-Policy e HSTS em produção.
- **Rate limiting** por arquivo (contato e recuperação de senha), sem Redis.
- **Sanitização de HTML** (whitelist) de todo conteúdo rico salvo pelo editor.
- Sessão segura (HttpOnly, SameSite, regeneração periódica de ID, expiração por inatividade).
- Uploads validados por MIME real e com **bloqueio de extensões executáveis**.
- **Auditoria** de ações administrativas (tabela `audit_logs`).
- Logs de erro sem exposição de detalhes ao visitante em produção.

Consulte [docs/security.md](docs/security.md) para detalhes.

---

## Desenvolvimento

- Ajuste `environment` para `development` em `config/local.php` para exibir erros detalhados.
- Sirva localmente apontando o servidor para `public/` (ou use o servidor embutido do PHP a partir de `public/`).
- Verifique a sintaxe dos arquivos com `php -l`.

---

## Produção

- Mantenha `environment` como `production` em `config/local.php` (`display_errors` desligado).
- Configure HTTPS (o sistema envia HSTS em produção).
- Garanta que `config/`, `storage/` e os arquivos sensíveis **não** sejam servidos diretamente (há `.htaccess` de proteção).
- Confira permissões de escrita apenas onde necessário (`storage/`, `public/uploads/`, `config/` na instalação).

---

## Backup

Recomendação de backup regular:

- **Banco de dados**: `mysqldump -u USUARIO -p NOME_DO_BANCO > backup.sql`
- **Arquivos enviados**: copie `public/uploads/`.
- **Configuração**: preserve `config/local.php` (contém credenciais — armazene com segurança).

Automatize via cron/tarefa agendada e mantenha cópias externas.

---

## Cache

Cache de dados por arquivo (`CacheService`) em `storage/cache/data`, sem Redis.
É usado para configurações, menus e sitemap. Limpe pelo painel em
**Sistema → Limpar cache** ou programaticamente com `CacheService::flush()`.
Entradas expiradas podem ser removidas periodicamente via cron
(`CacheService::purgeExpired()`).

## Internacionalização (i18n)

O site nasce em pt-BR, mas os textos ficam preparados para tradução. Os
dicionários vivem em `resources/lang/{locale}.php` e são resolvidos por chave
com o helper `__('grupo.chave')`. O locale ativo vem da configuração `locale`
(fallback pt-BR). Um dicionário `en.php` de exemplo acompanha o projeto.

## Testes

Suíte de testes de unidade sem dependências externas (não requer Composer/PHPUnit):

```bash
php tests/run.php
```

Cobre sanitização de HTML, rate limiting, cache e helpers/i18n. Testes que
dependeriam do banco são evitados para rodar em qualquer ambiente.

## Tarefas agendadas (cron)

Algumas rotinas devem rodar periodicamente (publicação de notícias agendadas,
limpeza de cache/rate limit/notificações). Veja
[docs/deployment.md](docs/deployment.md#tarefas-agendadas-cron).

## Documentação adicional

- [docs/architecture.md](docs/architecture.md) — arquitetura e fluxo de requisição
- [docs/database.md](docs/database.md) — modelo de dados
- [docs/migrations.md](docs/migrations.md) — regras e uso de migrations
- [docs/security.md](docs/security.md) — mecanismos de segurança
- [docs/cms.md](docs/cms.md) — gestão de conteúdo (home modular, notícias, páginas)
- [docs/admin.md](docs/admin.md) — guia do painel administrativo
- [docs/media.md](docs/media.md) — biblioteca de mídia e otimização de imagens
- [docs/seo.md](docs/seo.md) — SEO, sitemap, redirects
- [docs/email.md](docs/email.md) — configuração e templates de e-mail
- [docs/deployment.md](docs/deployment.md) — implantação em produção e cron
