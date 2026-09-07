# Implantação (Deploy)

## Requisitos do servidor

- PHP >= 8.0 com `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`.
- Recomendado: `gd` (otimização de imagens/WebP), `curl`, `zip`.
- MySQL >= 5.7 / MariaDB >= 10.3.
- Apache com `mod_rewrite` (ou Nginx equivalente).

Sem a extensão `gd`, a biblioteca de mídia funciona, mas não gera variantes
redimensionadas nem WebP (armazena apenas o original). O painel de
**Diagnóstico** indica o que está disponível.

## DocumentRoot

Duas opções:

1. **Recomendado** — aponte o DocumentRoot para `public/`. Use o `public/.htaccess` (já incluído).
2. **Raiz do projeto** — aponte para a raiz. O `.htaccess` e o `index.php` da raiz encaminham tudo para `public/index.php`. Assets e uploads são servidos diretamente.

## Passos

1. Envie os arquivos (via git, rsync ou FTP).
2. Ajuste permissões de escrita:
   - `config/` (necessário durante a instalação para gerar `local.php`)
   - `storage/` e subpastas
   - `public/uploads/`
3. Acesse o site e conclua o **instalador** em `/install`.
4. Após instalar, confira que:
   - `config/installed.lock` existe.
   - `config/local.php` existe e **não** está acessível publicamente.
   - `environment` está como `production` em `config/local.php`.

## Nginx (exemplo)

```nginx
server {
    listen 80;
    server_name exemplo.com;
    root /var/www/projeto/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Bloqueia execução em uploads
    location ^~ /uploads/ {
        location ~ \.php$ { return 403; }
    }

    # Protege pastas sensíveis
    location ~ ^/(config|storage|app|database|routes)/ { deny all; }
}
```

## Pós-deploy

- Configure HTTPS (o app envia HSTS em produção).
- Configure o SMTP no painel e envie um e-mail de teste.
- Configure as informações do site, SEO, redes sociais e menus.
- Agende backups (ver README).

## Tarefas agendadas (cron)

Algumas rotinas dependem de execução periódica. Em hospedagem compartilhada,
crie um endpoint ou script CLI que chame estes métodos e agende via cron/painel.

| Frequência sugerida | Ação | Método |
| ------------------- | ---- | ------ |
| A cada 5 min        | Publicar notícias agendadas cuja data chegou | `News::publishDueScheduled()` |
| Diária              | Remover entradas de cache expiradas | `CacheService::purgeExpired()` |
| Diária              | Limpar limites de taxa expirados | `RateLimiter::purgeExpired()` |
| Semanal             | Remover notificações administrativas antigas | `NotificationService::purgeOld($dias)` |

Exemplo de script CLI (`cron.php` na raiz, protegido para não ser acessível via web):

```php
<?php
require __DIR__ . '/config/constants.php';
require APP_PATH . '/Core/Autoloader.php';
$a = new \App\Core\Autoloader(); $a->addNamespace('App', APP_PATH); $a->register();
\App\Core\Config::load();
require HELPERS_PATH . '/helpers.php';

\App\Models\News::publishDueScheduled();
\App\Services\CacheService::purgeExpired();
\App\Services\RateLimiter::purgeExpired();
```

```cron
*/5 * * * * php /var/www/projeto/cron.php >/dev/null 2>&1
```

> A publicação agendada também ocorre naturalmente quando o site é acessado
> (as consultas de notícias já filtram por `published_at <= NOW()`), mas o cron
> garante consistência de status e notificações.

## Atualizações

- Ao publicar novas versões que alterem o banco, adicione **novas migrations** (nunca edite as existentes).
- Execute as migrations pendentes por **Administração → Sistema → Executar migrations** (requer `system.manage`) ou pelo instalador em um ambiente novo. Faça **backup do banco** antes.
- Após atualizar, use **Sistema → Limpar cache** se necessário.
- Nunca versione `config/local.php`.

## Integração com a API do jogo

O website consome a API oficial do jogo (`/api/v1`) como cliente server-side.

Requisitos adicionais em produção:

- Extensão **cURL** habilitada no PHP (o cliente HTTP a utiliza).
- A **Base URL** da API deve usar **HTTPS** e não pode apontar para endereços
  locais/privados (bloqueio anti-SSRF).

Passos:

1. Rode as migrations/seeds em **Admin → Sistema → Executar migrations** para
   criar as configurações da integração (seed `011_game_api_settings.sql`).
2. Em **Admin → Integrações → API do Jogo**, informe a Base URL (com `/api/v1`),
   o Client ID (se houver), o timeout e ative a integração.
3. Clique em **Testar conexão** (executa `GET /health`).

Desenvolvimento local (dois projetos):

```bash
# API do jogo (repositório do jogo)
cd api && npm install && npm run seed && npm run dev   # porta 4000

# Website PHP: DocumentRoot em public/ (ou servidor embutido a partir de public/)
```

Em dev, a Base URL pode ser `http://localhost:4000/api/v1` (localhost é
permitido fora de produção). Ver [game-api.md](game-api.md).
