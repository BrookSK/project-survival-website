# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue, de forma pragmática, o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)
e o versionamento adota [SemVer](https://semver.org/lang/pt-BR/).

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
