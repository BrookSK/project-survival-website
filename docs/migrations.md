# Migrations

O sistema possui um mecanismo próprio de migrations em `app/Core/Migrator.php`, com controle na tabela `migrations`.

## Regra fundamental: imutabilidade

> **NUNCA edite um arquivo de migration já criado e versionado.**

Uma vez versionada, a migration é imutável. Qualquer mudança de schema exige uma **nova** migration.

### Exemplo

Existe:
```
002_create_users_table.sql
```

É preciso adicionar a coluna `phone`. **Não** altere o arquivo 002. Crie:

```
015_add_phone_to_users.sql
```

com apenas a alteração:

```sql
ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) NULL AFTER `email`;
```

Ao adicionar uma migration que altera o schema, atualize também (recrie) `database/schema/schema.sql` para refletir o estado final — esse arquivo é apenas documentação e não é executado.

## Nomenclatura

Arquivos numerados sequencialmente, executados em ordem alfabética/numérica:

```
001_create_migrations_table.sql
002_create_users_table.sql
003_create_roles_permissions_tables.sql
...
```

## Como as migrations são executadas

Durante a **instalação**, o instalador executa todas as migrations pendentes e os seeds automaticamente.

Programaticamente, o `Migrator` oferece:

- `available()` — lista os arquivos disponíveis.
- `executed()` — lista as migrations já aplicadas (da tabela `migrations`).
- `pending()` — diferença entre as duas.
- `migrate()` — executa as pendentes e registra cada uma.
- `seed()` — executa os seeds (idempotentes).

A mesma migration nunca roda duas vezes: cada execução é registrada na tabela `migrations` (chave única em `migration`).

## Seeds

Seeds ficam em `database/seeds/` e são **idempotentes** (usam `INSERT ... ON DUPLICATE KEY UPDATE`), podendo ser reexecutados sem duplicar dados. Populam permissões, perfis, configurações padrão, páginas essenciais e menus iniciais.
