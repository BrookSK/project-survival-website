# Solicitações de titulares (DSAR)

Fluxo de atendimento aos direitos do titular (tabela `privacy_requests`).

## Tipos

`access`, `correction`, `deletion`, `portability`, `consent_revocation`,
`information`, `other`.

## Origem das solicitações

- **Jogador** — pela Central de Privacidade (`/conta/privacidade`): exportação
  (acesso/portabilidade), gestão de consentimento e solicitação de exclusão.
- **Admin** — acompanha e trata em **Admin → Privacidade → Solicitações**.

## Estados

```text
pending → in_review → awaiting_user → completed
                         ↘ rejected
                         ↘ cancelled
```

Cada mudança de status registra o responsável e (opcionalmente) uma nota interna.

## Verificação de identidade

Antes de fornecer dados ou executar exclusão, **verifique a identidade do
solicitante**. Não envie dados pessoais apenas porque alguém conhece o e-mail.
A tela de detalhe da solicitação exibe esse lembrete.

## Exportação (acesso/portabilidade)

- Gera JSON com: conta (player_id/username/email/name), consentimentos e
  solicitações do titular.
- **Nunca** inclui senha, hash, `access_token`, `refresh_token` ou segredos.
- Arquivo em `storage/exports` (fora de `/public`), com token de download
  (hash guardado) e expiração. Download exige o próprio jogador autenticado.

## Exclusão

1. Jogador solicita (confirmação forte "EXCLUIR") ou admin registra.
2. Verificação de identidade e de dependências.
3. **Game API**: a conta do jogador é encerrada pelo processo da API do jogo.
   (Endpoint de exclusão na Game API é uma pendência — ver auditoria.)
4. **Website**: ao concluir (permissão `privacy.delete`), os consentimentos do
   titular são removidos (`PrivacyConsent::purgePlayer`).
5. Registros exigidos por lei/segurança são preservados/anonimizados, não
   apagados indiscriminadamente.
6. Registro da operação em auditoria (ação, sem copiar PII).

## Auditoria

Ações relevantes registram: usuário, ação, recurso (`public_id`), IP, data.
O log grava a **ação**, não os dados pessoais do titular.
