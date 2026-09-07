# Painel administrativo

Guia de referência da área administrativa (`/admin`).

## Acesso e permissões

O acesso é controlado por **perfis (roles)** e **permissões** granulares
(ex.: `news.create`, `media.upload`, `system.manage`). O menu lateral exibe
apenas os itens permitidos ao usuário atual.

Perfis padrão:

- **Super Administrador** — acesso total.
- **Administrador** — acesso amplo (exceto gestão de perfis e `system.manage`).
- **Editor** — conteúdo e mídia.

## Seções

### Conteúdo
- **Home** — seções modulares da página inicial (ordenação por arrastar).
- **Páginas** — páginas do site (soft delete, imagem de destaque).
- **Notícias** — CMS de notícias (agendamento, destaque, lixeira, lote).
- **Categorias** — categorias de notícias (imagem, ativo/inativo).
- **FAQ** — perguntas frequentes por categoria.
- **Galeria** — álbuns e itens.
- **Vídeos** — trailers e vídeos por URL.
- **Mídia** — biblioteca de imagens (ver [media.md](media.md)).
- **Banners** — banners com imagem desktop/mobile.
- **Menus** — menus de cabeçalho e rodapé.

### Comunicação
- **Mensagens** — mensagens do formulário de contato.
- **Notificações** — sino na barra superior com eventos recentes.

### Sistema
- **Redes sociais** — links do rodapé.
- **Usuários / Perfis** — gestão de acesso.
- **E-mails** — templates de e-mail (ver [email.md](email.md)).
- **Redirects** — redirecionamentos 301/302.
- **Configurações** — grupos: Gerais, E-mail, SEO, Redes sociais, Sistema,
  Cookies/LGPD.
- **Auditoria** — log de ações administrativas com filtros.
- **Sistema** — informações do ambiente, migrations e limpeza de cache.
- **Diagnóstico** — health check (extensões, permissões, banco, SMTP).

## Manutenção pelo painel

- **Migrations**: em *Sistema*, um administrador com `system.manage` pode
  executar migrations pendentes após uma atualização de código. Faça backup do
  banco antes.
- **Limpar cache**: em *Sistema*, limpa o cache de dados (configurações, menus,
  sitemap) caso alterações não apareçam imediatamente.

## Notificações

Eventos como novas mensagens de contato geram notificações
(`admin_notifications`). O contador de não lidas aparece no sino da barra
superior; a página de notificações marca todas como lidas ao abrir.

## Auditoria

Ações de escrita registram entradas em `audit_logs` (usuário, ação, módulo,
alvo, descrição, IP). Útil para rastreabilidade e investigação.
