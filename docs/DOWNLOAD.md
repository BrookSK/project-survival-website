# Download público

Como o site disponibiliza o download do Project Survival, consumindo a
infraestrutura oficial de releases da Game API. O site **não** publica releases
nem hospeda o instalador — apenas consulta e aponta para o artefato oficial.

## Páginas e rotas

| Rota | O que faz |
| ---- | --------- |
| `GET /download` | Página de download: versão, plataforma, tamanho, SHA-256, requisitos, como instalar, suporte. |
| `GET /download/project-survival` | **Rota permanente**: resolve a release atual e redireciona (302) ao instalador oficial. |

A rota permanente não muda entre versões — o botão do site aponta sempre para
ela. Quando sair 1.0.1, 1.1.0, 2.0.0…, nada precisa ser alterado no site.

## Fonte oficial (nunca hardcoded)

O site consome o contrato público da Game API:

```
GET /api/v1/public/download/{channel}   (channel padrão: stable)
→ { product, channel, version, platform, notes, released_at,
    requirements:{os,arch,graphics,internet},
    installer:{ filename, url, permanent_url, sha256, size },
    game:{ filename, url }, download_url }
```

- **Versão, tamanho e SHA-256** vêm sempre da resposta oficial. O site nunca
  inventa nem calcula.
- **URL do botão** = `download_url` → `installer.permanent_url` → `installer.url`
  (nesta ordem de preferência), resolvida por `ReleaseInformation::bestInstallerUrl()`.

## Cache e fallback

`GameReleaseService` cacheia a release por canal (TTL configurável em
**Admin → Configurações → Releases / Download**) e mantém uma cópia *stale*
persistente. Se a API estiver indisponível, usa a cópia stale; se não houver
nenhuma, a página mostra "Informações de download temporariamente indisponíveis"
— **sem erro fatal e sem inventar versão**.

## Segurança do redirect

Antes de redirecionar, `UrlGuard::isSafeDownloadUrl()` valida a URL:

- Esquema `http`/`https` (em produção, **HTTPS obrigatório**).
- Bloqueia hosts privados/reservados (anti-SSRF).
- Allowlist opcional de hosts (setting `download_allowed_hosts`, CSV) além dos
  hosts derivados da base URL da API e da própria release.

A URL de destino **nunca** vem de parâmetro do usuário — apenas da Game
API/configuração. Assim não há open-redirect nem SSRF.

## Configuração (Admin, sem `.env`)

- **Releases / Download**: `release_channel` (stable/beta/dev), `release_cache_ttl`,
  `download_allowed_hosts`.
- **URLs do site**: download/store/account/support/privacy/terms/website
  (combinar domínios com a equipe do jogo). Use o helper `game_url('download')` etc.

## Atualizações

O site entrega apenas o **download inicial**. As atualizações do jogo são feitas
automaticamente pelo **launcher/updater** oficiais — o site não implementa
atualizador nem distribui patches. A página `/updates` explica esse fluxo e
mostra a versão/novidades atuais.
