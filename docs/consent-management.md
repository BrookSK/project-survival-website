# Gestão de consentimento

Modelo técnico de consentimento (tabela `privacy_consents`, serviço
`ConsentService`).

## Tipos de consentimento

| Tipo | Obrigatório | Onde |
| ---- | ----------- | ---- |
| `terms` (Termos de Uso) | Sim | Cadastro |
| `privacy` (Política de Privacidade) | Sim | Cadastro |
| `marketing` | Não (opt-in) | Cadastro e Central de Privacidade |

Cada consentimento é único por `(player_id, consent_type)` e guarda a **versão**
do documento aceito, `granted`, IP, user agent e timestamps (`granted_at` /
`revoked_at`).

## Cadastro

No `/criar-conta`, o jogador deve marcar o aceite de Termos + Privacidade
(obrigatório). Marketing é um checkbox separado e opcional. Após o cadastro e o
login automático, os consentimentos são gravados com a versão vigente de cada
documento (`ConsentService::recordRequiredAcceptance` + `recordMarketing`).

> Não se exige consentimento de marketing para criar conta. Comunicações
> essenciais (recuperação de senha, segurança) independem de marketing.

## Reaceite (nova versão)

`ConsentService::pendingReacceptance($playerId)` compara a versão aceita com a
versão publicada vigente de cada documento obrigatório. Havendo divergência, o
layout exibe um **banner de reaceite** com links para os documentos e para a
Central de Privacidade. Documentos ainda não publicados não forçam aceite.

## Revogação

Na Central de Privacidade o jogador pode desativar o marketing a qualquer
momento (`granted = 0`, `revoked_at` preenchido). O tratamento baseado nesse
consentimento deve cessar.

## Exclusão

Ao concluir uma solicitação de exclusão (Admin, com permissão `privacy.delete`),
os consentimentos do titular são removidos (`PrivacyConsent::purgePlayer`).
