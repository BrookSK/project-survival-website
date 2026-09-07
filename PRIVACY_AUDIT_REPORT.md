# PRIVACY_AUDIT_REPORT

Relatório de auditoria de privacidade do website (Prompt 5). Reflete o
comportamento real do sistema na data desta entrega. **Não substitui revisão
jurídica especializada.**

- Versão do app: **2.2.0**
- Escopo: website PHP/MVC (`project-survival-website`). A Game API (Node) é
  sistema separado e autoridade sobre a conta do jogador.

## 1. Dados encontrados e onde são armazenados

| Dado | Local | Observação |
| ---- | ----- | ---------- |
| Admin: nome, e-mail, hash de senha, IP último acesso | `users` (MySQL) | Senha só como hash bcrypt |
| Contato: nome, e-mail, assunto, mensagem, IP, user agent | `contact_messages` | Formulário `/contato` |
| Auditoria: usuário, ação, módulo, IP, user agent | `audit_logs` | Ações administrativas |
| Tentativas de login (admin): e-mail, IP | `login_attempts` | Anti brute force |
| Recuperação de senha (admin): e-mail, hash do token | `password_resets` | Token nunca em claro |
| Consentimentos: player_id, tipo, versão, IP, UA | `privacy_consents` | LGPD |
| Solicitações de titular: player_id, e-mail, tipo, status | `privacy_requests` | LGPD |
| Exportações: player_id, hash do token, caminho | `data_exports` | Arquivo em `storage/exports` |
| Sessão do jogador (tokens) | Sessão server-side (cookie `GAMESITE_SESSID`) | Nunca em HTML/JS/log |

Conta do jogador (e-mail/username/id), entitlements, inventário e loja: **na
Game API**, consumidos ao vivo, não replicados no site.

## 2. Integrações (terceiros)

- **Game API (Project Survival)** — conta/entitlements/loja do jogador.
- **Provedor SMTP** (configurável) — envio de e-mails transacionais.
- **Não há**: gateway de pagamento ativo, analytics, CDN externo, captcha.

## 3. Políticas criadas

Documentos versionados e administráveis (rascunho/publicado/arquivado), servidos
em `/privacidade`, `/termos`, `/termos-de-compra`, `/reembolso`, `/cookies`.
Ficam vazios até serem redigidos e publicados no painel (nada fictício).

## 4. Consentimentos

- Termos + Privacidade: obrigatórios no cadastro, gravados com a versão vigente.
- Marketing: opt-in separado, revogável na Central de Privacidade.
- Reaceite detectado quando uma nova versão obrigatória é publicada.

## 5. Direitos do titular

- Exportação (acesso/portabilidade) em JSON sem segredos, com expiração.
- Gestão de consentimento (marketing).
- Solicitação de exclusão (com verificação; não apaga na hora).
- Admin trata solicitações com fluxo de status e auditoria.

## 6. Segurança verificada

- CSRF em todos os POST novos; RBAC (`privacy.*`) em todo o admin.
- Sem IDOR: rotas privadas usam a sessão do jogador (não `{id}` manipulável);
  download de exportação valida ownership + token + expiração.
- XSS: conteúdo das políticas sanitizado (HtmlSanitizer) ao salvar.
- Exportações fora de `/public`; sem path traversal (caminho vem do banco).
- `noindex` em `/conta`, `/login`, `/criar-conta` (robots + meta).
- Máscara de e-mail no painel; auditoria registra a ação, não a PII.

## 7. Riscos encontrados e correções

| Risco | Situação | Correção |
| ----- | -------- | -------- |
| Banner de cookies só "Aceitar" | Corrigido | Aceitar/Recusar/Configurar + preferências |
| Sem registro de consentimento | Corrigido | `privacy_consents` + cadastro + reaceite |
| Sem canal de direitos do titular | Corrigido | Central de Privacidade + admin de solicitações |
| Exportação poderia vazar segredos | Mitigado | Payload sem tokens/hash; teste automatizado garante |
| Páginas legais placeholders sem versão | Corrigido | Documentos versionados com vigência/histórico |

## 8. Pontos que dependem de decisão jurídica

- Texto final de todos os documentos legais (revisão jurídica obrigatória).
- Bases legais definitivas por tratamento (o `data-map.md` traz sugestões).
- Prazos de retenção definitivos (hoje configuráveis, com padrões de partida).
- Idade mínima / contas de menores (não presumido; configurar se aplicável).

## 9. Pendências técnicas

- **Exclusão de conta na Game API**: a API do jogo ainda não expõe endpoint de
  exclusão de conta. Hoje a solicitação é registrada e tratada; a purga no site
  remove consentimentos (com `privacy.delete`). Quando a Game API oferecer o
  endpoint, integrar ao fluxo de exclusão.
- Rotinas de retenção automática (contato/auditoria) podem ser agendadas via
  cron usando os settings de retenção.
- As páginas `pages` legais antigas (`politica-de-privacidade`, `termos-de-uso`,
  `politica-de-cookies`) podem ser despublicadas ou redirecionadas pelo admin
  para os novos documentos versionados.

## 10. Testes executados

- Lint: **225 arquivos PHP, 0 erros** (`php -l`).
- Boot test lógico: rotas web/admin/api parseiam; 9 classes de privacidade
  carregam; `mask_email` funcional; APP_VERSION 2.2.0.
- Suíte de unidade: **52 testes OK / 0 falhas / 84 asserções**
  (`php tests/run.php`), incluindo `PrivacyTest` (máscara de e-mail e contrato
  de exportação sem segredos).

## 11. Resultado final

A camada de privacidade está implementada e integrada ao sistema existente,
refletindo o que a aplicação realmente coleta e trata. Os documentos legais e as
configurações de empresa/contato ficam administráveis e devem ser preenchidos e
**revisados juridicamente** antes da publicação em produção.
