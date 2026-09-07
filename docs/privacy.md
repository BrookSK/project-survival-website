# Privacidade e proteção de dados

Este documento descreve, do ponto de vista técnico, como o website trata dados
pessoais. Ele reflete o comportamento real do sistema — não é a política pública
(essa é gerida no painel, versionada, e servida em `/privacidade`).

> A implementação técnica **não substitui revisão jurídica especializada**. Os
> documentos legais devem ser revisados por um profissional antes da publicação
> definitiva.

## Princípios adotados

- **Privacy by Design / Security by Default**: dados privados são privados por
  padrão; endpoints privados exigem sessão.
- **Minimização**: o site guarda apenas o necessário. A conta do jogador pertence
  à Game API; o site não replica esses dados.
- **Least Privilege**: permissões `privacy.*` segregadas; exclusão/exportação em
  massa exigem permissões específicas.
- **Auditabilidade**: ações administrativas relevantes são registradas (a ação,
  não os dados pessoais em si).
- **Transparência**: cookies e integrações documentados conforme o que realmente
  existe.

## Fronteira de responsabilidade

- **Game API (Node)** — autoridade sobre a conta do jogador, entitlements,
  inventário e loja. O site consome via REST e nunca persiste esses dados.
- **Website (PHP)** — guarda dados administrativos, conteúdo do site, mensagens
  de contato, logs/auditoria e, no contexto de privacidade, **consentimentos**,
  **solicitações de titulares** e **exportações**.

## Documentos legais

Documentos versionados (tabela `privacy_policies` + `privacy_policy_versions`),
geridos em **Admin → Privacidade → Documentos legais**:

- Política de Privacidade — `/privacidade`
- Termos de Uso — `/termos`
- Termos de Compra — `/termos-de-compra`
- Política de Reembolso — `/reembolso`
- Política de Cookies — `/cookies`

Uma versão publicada é imutável; alterações criam nova versão e arquivam a
anterior (nunca apagam). A publicação registra responsável e data.

## Direitos do titular (jogador)

Central em **Minha conta → Privacidade** (`/conta/privacidade`):

- **Acesso/portabilidade** — exportação em JSON (`/conta/privacidade/exportar`).
  O arquivo fica em armazenamento privado, com token de download e expiração.
  Nunca inclui senhas, hashes ou tokens.
- **Consentimentos** — gerenciar marketing (opt-in/out).
- **Exclusão** — solicitação com confirmação forte; não apaga imediatamente
  (verificação + dependências da Game API + obrigações legais).

## Consentimento

- Aceite de **Termos de Uso** e **Política de Privacidade** é obrigatório no
  cadastro, registrado com a versão vigente.
- **Marketing** é consentimento separado e opcional. Comunicações essenciais
  (segurança, recuperação de conta) não dependem dele.
- Quando uma nova versão obrigatória é publicada, o sistema detecta quem precisa
  reaceitar (banner de reaceite).

Detalhes em [consent-management.md](consent-management.md).

## Referências

- [data-map.md](data-map.md) — inventário e fluxo de dados
- [consent-management.md](consent-management.md) — modelo de consentimento
- [data-retention.md](data-retention.md) — retenção
- [data-subject-requests.md](data-subject-requests.md) — solicitações
- [cookies.md](cookies.md) — cookies
- [security-incidents.md](security-incidents.md) — resposta a incidentes
- [PRIVACY_AUDIT_REPORT.md](../PRIVACY_AUDIT_REPORT.md) — relatório de auditoria
