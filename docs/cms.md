# CMS — Gestão de conteúdo

Este documento descreve como o conteúdo do site é gerenciado pelo painel.

## Editor de conteúdo rico

Campos de conteúdo (páginas, notícias, respostas de FAQ, seções da home) usam um
editor leve (`public/assets/js/editor.js`) aplicado a `textarea[data-editor]`.
Ele oferece formatação básica (títulos, negrito, itálico, listas, citações,
links, imagens) via `contenteditable`.

Todo HTML salvo passa pelo `HtmlSanitizer` (whitelist rígida). Tags e atributos
fora da lista são removidos; `iframe` só é aceito de YouTube/Vimeo. Consulte
[security.md](security.md) para os detalhes da sanitização.

## Home modular

A página inicial é montada a partir de **seções** (`home_sections`) ativas e
ordenadas. Cada seção tem um `type` que define como é renderizada:

| Tipo          | Renderização                                   |
| ------------- | ---------------------------------------------- |
| `content`     | Bloco de texto + imagem + botão                |
| `features`    | Cartões de destaques                           |
| `screenshots` | Grade de álbuns da galeria                     |
| `trailer`     | Vídeo em destaque (embed)                       |
| `news`        | Últimas notícias / notícias em destaque        |
| `faq`         | Acordeão de perguntas frequentes               |
| `cta`         | Faixa de chamada para ação                      |

Gerencie em **Administração → Home**. A ordem é definida arrastando os itens
(persistida via endpoint de reordenação).

## Banners

Banners (`banners`) suportam imagem **desktop** e **mobile** (`image_mobile`).
No site, um elemento `<picture>` serve a imagem adequada ao viewport. A posição
padrão é `home_hero`.

## Notícias

Recursos do CMS de notícias (**Administração → Notícias**):

- **Status**: rascunho, agendada, publicada, arquivada.
- **Agendamento**: notícias `scheduled` com data futura são publicadas quando a
  data chega (ver rotina de cron em [deployment.md](deployment.md)).
- **Destaque**: limitado por `featured_news_limit` (configuração do sistema).
- **Soft delete**: excluir move para a lixeira (`deleted_at`); os dados não são
  apagados fisicamente.
- **Filtros e ordenação**: por status, busca e colunas (data, título, views).
- **Ações em lote**: publicar, arquivar ou excluir várias notícias.
- **Categorias**: com imagem e status ativo/inativo.

## Páginas

Páginas (`pages`) têm status (rascunho/publicada/arquivada), imagem de destaque e
soft delete. Páginas de sistema (ex.: políticas legais) são protegidas contra
exclusão. Páginas adicionais ficam acessíveis em `/p/{slug}`.

## FAQ, Galeria e Vídeos

- **FAQ**: perguntas agrupadas por categoria; respostas sanitizadas.
- **Galeria**: álbuns com itens; lightbox no site.
- **Vídeos**: cadastrados por URL; provedor, ID e thumbnail detectados
  automaticamente. Um vídeo pode ser marcado como destaque (trailer).

## Menus

Menus por localização (`header`, `footer`). Itens de menu são cacheados por
5 minutos; o cache é invalidado automaticamente ao criar/editar/excluir itens.
