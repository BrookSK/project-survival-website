# SEO

Recursos de otimização para mecanismos de busca e compartilhamento social.

## Metadados por página

O `SeoService::build()` monta os metadados de cada página: `title`,
`description`, `keywords`, `robots`, `canonical`, além de Open Graph e Twitter
Card. As views passam um array `$seo` para o layout do site, que renderiza as
tags no `<head>`.

Notícias e páginas possuem campos próprios de SEO (`seo_title`,
`seo_description`, `og_image`) que têm precedência sobre os valores padrão.

## Dados estruturados (JSON-LD)

O layout público injeta JSON-LD `WebSite` por padrão. Páginas específicas podem
fornecer `$jsonLd` adicional (ex.: `Organization` na home).

## sitemap.xml

Gerado dinamicamente em `/sitemap.xml` a partir das páginas fixas, páginas
publicadas, notícias publicadas e álbuns ativos. O resultado é **cacheado por
1 hora** (`CacheService`) para evitar varredura do banco a cada acesso de robô.
O cache é limpo ao rodar migrations ou pelo botão de limpeza no painel.

## robots.txt

Servido em `/robots.txt`. Bloqueia `/admin` e `/install` e aponta para o
sitemap. A indexação geral pode ser controlada pela configuração
`seo_indexable`.

## Configurações relacionadas

- `seo_google_verification` — meta tag de verificação do Google Search Console.
- `seo_indexable` — controle global de indexação.
- `default_share_image` — imagem padrão para compartilhamento (OG/Twitter).

## Redirects

Redirecionamentos 301/302 são gerenciáveis em **Administração → Redirects** e
aplicados pelo `RedirectMiddleware` antes do roteamento normal, preservando o
valor de SEO ao mover ou renomear URLs.
