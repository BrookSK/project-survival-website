# Retenção de dados

Prazos de retenção são **configuráveis** em **Admin → Configurações →
Privacidade** (grupo `privacy`). Os valores padrão abaixo são pontos de partida
e devem ser ajustados conforme necessidade operacional e obrigações legais —
não representam aconselhamento jurídico.

| Dado | Setting | Padrão | Observação |
| ---- | ------- | ------ | ---------- |
| Mensagens de contato | `retention_contact_days` | 365 dias | Podem ser necessárias para suporte |
| Logs de auditoria | `retention_audit_days` | 730 dias | Segurança/rastreabilidade |
| Exportações de dados | `retention_export_days` | 7 dias | Validade do link de download |
| Prazo interno de resposta a titulares | `privacy_request_sla_days` | 15 dias | Meta interna, não prazo legal |

## Princípios

- **Não** apagar dados financeiros/registros exigidos por lei apenas para
  "limpar" — preferir anonimização quando o dado pessoal não for mais
  necessário mas o registro precisar ser preservado.
- **Não** guardar logs indefinidamente sem necessidade.
- Backups que contenham dados pessoais também estão sujeitos à política de
  retenção e segurança.

## Rotinas de limpeza (cron)

- `DataExport::expireOld()` — marca exportações vencidas e devolve os caminhos de
  arquivo para remoção física.
- `RateLimiter::purgeExpired()`, `CacheService::purgeExpired()`,
  `NotificationService::purgeOld()` — já documentadas em `deployment.md`.

Retenção de contato/auditoria pode ser implementada como rotina futura usando os
settings acima; hoje os prazos são documentados/configuráveis.
