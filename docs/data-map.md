# Mapa de dados (Data Map)

Inventário real dos dados pessoais tratados pelo website, onde ficam, finalidade
e base legal sugerida. Reflete o código atual — deve ser atualizado sempre que
uma funcionalidade nova coletar dados.

## Fluxo geral

```text
Navegador
   │
   ▼
Website PHP ── sessão (cookie GAMESITE_SESSID, HttpOnly/SameSite)
   │
   ├── Banco do site (MySQL): admin, contato, auditoria, consentimentos, solicitações
   │
   ├── Game API (REST) ── conta do jogador, entitlements, inventário, loja
   │
   └── Provedor SMTP ── envio de e-mails transacionais
```

## Dados no banco do site

| Dado | Tabela | Finalidade | Base legal sugerida |
| ---- | ------ | ---------- | ------------------- |
| Nome, e-mail, hash de senha, IP do último acesso (admin) | `users` | Autenticação do painel | Execução de contrato / legítimo interesse |
| Nome, e-mail, assunto, mensagem, IP, user agent | `contact_messages` | Responder contato | Legítimo interesse / execução de contrato |
| Usuário, ação, módulo, IP, user agent | `audit_logs` | Segurança e auditoria | Cumprimento de obrigação / legítimo interesse |
| E-mail, IP, sucesso | `login_attempts` | Proteção contra brute force | Legítimo interesse (segurança) |
| E-mail, hash do token, expiração | `password_resets` | Recuperação de senha do painel | Execução de contrato |
| player_id, tipo, versão, granted, IP, user agent | `privacy_consents` | Registro de consentimento | Consentimento / cumprimento de obrigação |
| player_id, e-mail, tipo, status | `privacy_requests` | Atender direitos do titular | Cumprimento de obrigação |
| player_id, hash do token, caminho do arquivo | `data_exports` | Portabilidade/acesso | Cumprimento de obrigação |

> Senhas do painel são armazenadas apenas como hash (`password_hash`, bcrypt).
> A senha **do jogador** nunca é armazenada pelo site — vai direto à Game API.

## Dados na Game API (não no site)

Conta do jogador (e-mail, username, id), entitlements, inventário, pedidos e
catálogo da loja. O site os **consome ao vivo** e não os persiste. Tokens do
jogador (access/refresh) vivem apenas na **sessão server-side**.

## Cookies

- `GAMESITE_SESSID` — cookie de sessão, **essencial** (autenticação/CSRF).
  HttpOnly, SameSite, Secure em produção.
- Preferência de consentimento de cookies — armazenada em `localStorage`
  (`cookie_consent`), não é cookie.
- **Não há** cookies de análise, marketing ou de terceiros no momento.

Ver [cookies.md](cookies.md).

## Terceiros com quem há troca de dados

| Terceiro | Dados | Finalidade |
| -------- | ----- | ---------- |
| Game API (Project Survival) | conta/entitlements/loja do jogador | Funcionalidade principal |
| Provedor SMTP (configurado) | e-mail do destinatário, conteúdo transacional | Envio de e-mails |

Não há, hoje, gateway de pagamento ativo, analytics, CDN externo ou captcha
configurados. Se qualquer um for adicionado, este mapa, a Política de
Privacidade e a de Cookies devem ser atualizados.

## Logs

Logs técnicos (`storage/logs`) podem conter IP, user agent, timestamp e evento.
**Nunca** registram senha, tokens (access/refresh), segredos ou dados de cartão.
