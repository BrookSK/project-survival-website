# Banco de dados

Todas as tabelas usam **InnoDB** e **`utf8mb4_unicode_ci`**.

## Tabelas

| Tabela | Descrição |
|--------|-----------|
| `migrations` | Controle das migrations executadas. |
| `users` | Usuários administrativos. |
| `roles` | Perfis de acesso (RBAC). |
| `permissions` | Permissões granulares. |
| `role_permissions` | Junção N:N perfil ↔ permissão. |
| `user_roles` | Junção N:N usuário ↔ perfil. |
| `settings` | Configurações administráveis (chave/valor por grupo). |
| `pages` | Páginas de conteúdo. |
| `page_meta` | Metadados de SEO das páginas. |
| `news` | Notícias. |
| `news_categories` | Categorias de notícias. |
| `news_category_relations` | Junção N:N notícia ↔ categoria. |
| `faqs` | Perguntas frequentes. |
| `faq_categories` | Categorias de FAQ. |
| `gallery_albums` | Álbuns da galeria. |
| `gallery_items` | Itens (imagem/vídeo) da galeria. |
| `menus` | Menus (por localização). |
| `menu_items` | Itens de menu (hierárquicos). |
| `banners` | Banners/slides. |
| `contact_messages` | Mensagens do formulário de contato. |
| `audit_logs` | Auditoria de ações administrativas. |
| `login_attempts` | Tentativas de login (anti brute-force). |
| `password_resets` | Tokens de recuperação de senha (hash). |

## Relacionamentos principais

- `users` ↔ `roles` via `user_roles`.
- `roles` ↔ `permissions` via `role_permissions`.
- `pages` 1:1 `page_meta`.
- `news` ↔ `news_categories` via `news_category_relations`.
- `faqs` N:1 `faq_categories` (SET NULL ao excluir a categoria).
- `gallery_items` N:1 `gallery_albums` (SET NULL).
- `menu_items` N:1 `menus`; `menu_items` self-reference via `parent_id`.
- `audit_logs` N:1 `users` (SET NULL — preserva o histórico mesmo após exclusão, com snapshot do nome).

## RBAC e Super Administrador

O perfil `super-admin` tem acesso **total**, tratado no código (`AuthService::can()` retorna `true`). As permissões associadas via `role_permissions` valem para os demais perfis.

## Referência

- Definições exatas (DDL): `database/migrations/*.sql`.
- Retrato consolidado de leitura: `database/schema/schema.sql`.
- Dados iniciais: `database/seeds/*.sql`.
