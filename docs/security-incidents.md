# Resposta a incidentes de segurança

Procedimento interno para tratar incidentes que possam afetar dados pessoais.
Documento operacional — não é promessa de segurança absoluta.

## Fluxo

```text
Detecção → Contenção → Análise → Correção → Avaliação de impacto → Comunicação (quando aplicável)
```

1. **Detecção** — via logs, monitoramento, relato de usuário ou terceiro.
2. **Contenção** — limitar o alcance (ex.: revogar sessões/tokens, bloquear
   acesso, desativar integração comprometida).
3. **Análise** — identificar causa, dados envolvidos e período.
4. **Correção** — corrigir a vulnerabilidade; girar segredos comprometidos.
5. **Avaliação de impacto** — quais titulares e dados foram afetados.
6. **Comunicação** — notificar autoridade/titulares quando exigido por lei e
   conforme orientação jurídica.

## Fontes de evidência

- `storage/logs/app-YYYY-MM-DD.log` — eventos, erros, rate limits, uploads
  recusados, requisições à Game API (sem segredos).
- `audit_logs` — ações administrativas.
- Provedor de e-mail / Game API — logs externos quando aplicável.

## Regras ao investigar

- Não exponha nem copie dados pessoais além do necessário.
- Logs nunca contêm senha, tokens, segredos ou dados de cartão; se algum
  aparecer, trate como incidente e corrija a origem do log.
- Preserve evidências antes de limpar.

## Prevenção (já implementada)

CSRF em escrita, RBAC, rate limiting, headers de segurança/CSP, sanitização de
HTML, sessão segura, uploads validados, tokens do jogador só em sessão
server-side, proteção anti-SSRF na URL da Game API. Ver `security.md`.
