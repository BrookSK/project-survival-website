# Cookies

Descrição técnica do uso de cookies. A Política de Cookies pública é gerida no
painel e servida em `/cookies`; a página de preferências fica em
`/privacidade/cookies`.

## Categorias reais

| Categoria | Existe hoje? | Exemplo | Consentimento |
| --------- | ------------ | ------- | ------------- |
| Essenciais | Sim | `GAMESITE_SESSID` (sessão/autenticação/CSRF) | Não requer |
| Análise/Estatística | Não | — | — |
| Marketing | Não | — | — |
| Terceiros | Não | — | — |

**O site não utiliza, no momento, cookies de análise, marketing ou de
terceiros.** Não há Google Analytics, GTM, pixels ou similares no código.

## Consentimento

- O banner (exibido quando `cookie_enabled` está ativo) oferece **Aceitar**,
  **Recusar opcionais** e **Configurar**.
- A escolha é salva em `localStorage` (`cookie_consent = { choice, at }`).
- Cookies **essenciais** são necessários para o funcionamento e não dependem da
  escolha.
- Cookies **opcionais** só devem ser carregados após consentimento. Como não há
  nenhum hoje, nada é carregado condicionalmente.

## Hook para o futuro

`site.js` expõe `window.onCookieConsent(choice)` e dispara o evento
`cookies:accepted`. Se um recurso opcional (ex.: analytics) for adicionado no
futuro, ele deve ser carregado **apenas** dentro desse hook — nunca antes do
consentimento. Ao fazê-lo, atualize esta página, a Política de Cookies e o mapa
de dados.
