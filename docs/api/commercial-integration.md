# Contrato de Integração Comercial — Website (PHP) ⇄ Game API (Node)

> **Status:** PROPOSTA DE CONTRATO. Os endpoints comerciais descritos aqui
> (fulfillment / refund / revoke server-to-server) **ainda NÃO existem** na Game
> API no momento em que este documento foi escrito. O website PHP foi construído
> para **consumir** este contrato assim que a Game API o implementar. Enquanto
> isso, o lado PHP opera de forma segura: registra pedidos/pagamentos, processa
> webhooks do gateway e mantém o fulfillment em estado `pending` com retry —
> **sem nunca conceder itens localmente**.

Este documento existe porque o repositório da Game API **não** faz parte deste
workspace. Ele descreve o que o site espera da Game API, como se autentica e
quais garantias de idempotência ambos os lados precisam respeitar.

---

## 1. Princípios inegociáveis

1. **A Game API é a única autoridade sobre entitlement/inventário.** O website
   **nunca** insere, concede ou remove itens do jogador em banco próprio.
2. **Nada de simulação.** O site não simula pagamento, nem fulfillment, nem
   entitlement. Um estado só avança mediante confirmação real (gateway ou Game
   API).
3. **Server → Server → confirmação.** O navegador nunca é fonte de verdade para
   preço, moeda ou status financeiro. Concessão de itens só acontece por chamada
   server-to-server autenticada entre site e Game API.
4. **Idempotência ponta a ponta.** Reenvios (webhooks duplicados, retries de
   rede, duplo clique) não podem gerar cobrança dupla nem entrega dupla.

---

## 2. O que a Game API JÁ expõe hoje (consumido pelo site)

Fonte: `docs/game-api.md`. Base: `/api/v1`.

| Método / rota | Uso no site | Auth do jogador |
| ------------- | ----------- | --------------- |
| `GET /store/categories` | Catálogo (`/loja`) | não |
| `GET /store/products` | Catálogo; traz `owned` se autenticado | opcional |
| `GET /store/products/:id` | Detalhe do produto | opcional |
| `POST /store/purchase` | Inicia um pedido `pending` (não cobra, não concede) | sim (Bearer do jogador) |
| `GET /player/entitlements` | Itens possuídos (fonte de verdade) | sim |
| `GET /player/inventory` | Inventário/pedidos | sim |

**Importante:** `POST /store/purchase` hoje apenas devolve
`{ order_id, status: "pending", payment, message }`. Ele **não** é o caminho de
concessão. A concessão real depende dos endpoints da seção 4, que ainda não
existem.

---

## 3. Autenticação server-to-server (service account)

As chamadas comerciais **não** usam o token do jogador. Elas usam uma
**service account** dedicada ao site, configurada no painel administrativo
(**Admin → Loja → Configurações**, grupo `payments`), sem `.env`:

- `game_api_service_client_id` — identificador do site como serviço.
- `game_api_service_secret` — segredo (armazenado como `is_secret`; nunca sai
  em HTML/log/resposta).

Cada requisição comercial envia:

```
X-Client-Id: <game_api_service_client_id>
Authorization: Bearer <credencial de serviço emitida pela Game API>
Idempotency-Key: <chave idempotente da operação>
Content-Type: application/json
```

A Game API deve validar o par client/secret e autorizar apenas o escopo
comercial (fulfillment/refund/revoke). O site trata os headers sensíveis como
secretos: `HttpClient` já os remove dos logs.

> **Anti-SSRF:** a Base URL da Game API passa pelo `UrlGuard` (bloqueio de hosts
> privados) antes de qualquer chamada, igual ao restante da integração.

---

## 4. Endpoints comerciais que a Game API PRECISA expor (a implementar)

Estes são os endpoints que o adapter do site (`GameFulfillmentService`) vai
consumir. **Ainda não existem.** Estão especificados aqui para que o time da
Game API os implemente. Nos testes do site eles são **mockados** (`MockHttpClient`).

### 4.1 `POST /commerce/fulfillments` — conceder itens de um pedido pago

Chamado **depois** que o pagamento foi confirmado como aprovado (o site já
consultou o gateway e confirmou o estado real). É a **única** forma de o jogador
receber o item.

Requisição:

```json
{
  "order_reference": "SITE-000123",
  "player_id": "player_abc",
  "items": [
    { "product_id": "prod_1", "sku": "gold_1000", "quantity": 1 }
  ],
  "payment_reference": "mp_9988776655",
  "currency": "BRL",
  "amount": 4990,
  "occurred_at": "2026-09-07T12:00:00Z"
}
```

- `Idempotency-Key`: **`order_reference + ":" + product_id`** (uma chave por
  item concedido). Reenvios com a mesma chave devem retornar o **mesmo**
  resultado, sem conceder de novo.
- `amount` em **centavos** (inteiro).

Resposta de sucesso (`200`/`201`):

```json
{
  "fulfillment_id": "ff_123",
  "status": "granted",
  "granted_items": [
    { "product_id": "prod_1", "entitlement": "gold_1000", "quantity": 1 }
  ],
  "idempotent_replay": false
}
```

- `idempotent_replay: true` indica que a chave já havia sido processada (o site
  trata como sucesso e **não** duplica nada).
- `status` esperado: `granted` (concedido) ou `accepted` (aceito, concessão
  assíncrona — o site mantém o fulfillment como `processing` e reconsulta).

Erros e como o site reage:

| HTTP | Significado | Reação do site |
| ---- | ----------- | -------------- |
| `400` | Payload inválido | Falha **permanente** — não faz retry; marca `failed`; alerta admin |
| `401` / `403` | Service account inválida/sem escopo | Falha permanente; alerta admin (config) |
| `404` | Produto/jogador inexistente | Falha permanente; marca `failed` |
| `409` | Conflito de idempotência com dados divergentes | Não concede; marca `failed`; investigação manual |
| `422` | Regra de negócio (ex.: item não elegível) | Falha permanente; `failed` |
| `429` | Rate limit | Retry com backoff |
| `5xx` / timeout / rede | Indisponível | **`fulfillment_pending`** + retry com backoff (limite de tentativas) |

### 4.2 `GET /commerce/fulfillments/:order_reference` — consultar concessão

Usado na **reconciliação** e na página do pedido para confirmar o estado real
sem depender do que o site acha que aconteceu.

Resposta:

```json
{
  "order_reference": "SITE-000123",
  "status": "granted",
  "items": [ { "product_id": "prod_1", "entitlement": "gold_1000", "quantity": 1, "granted_at": "2026-09-07T12:00:05Z" } ]
}
```

### 4.3 `POST /commerce/refunds` — revogar/estornar concessão (política de revoke)

Chamado quando um refund/chargeback é confirmado no gateway e a política do
produto define revogação. **Quem revoga o entitlement é a Game API**, nunca o
site.

Requisição:

```json
{
  "order_reference": "SITE-000123",
  "player_id": "player_abc",
  "reason": "refund",
  "payment_reference": "mp_9988776655",
  "items": [ { "product_id": "prod_1", "sku": "gold_1000", "quantity": 1 } ],
  "policy": "revoke"
}
```

- `Idempotency-Key`: **`order_reference + ":refund:" + product_id`**.
- `policy`: `revoke` (remover entitlement) ou `keep` (manter item; apenas
  registrar o estorno financeiro). A decisão é do operador/política do produto,
  registrada no site com auditoria.

Resposta:

```json
{ "refund_id": "rf_123", "status": "revoked", "idempotent_replay": false }
```

`status`: `revoked`, `kept` ou `accepted` (assíncrono).

### 4.4 (Opcional) `POST /commerce/orders` — reserva/validação de pedido

Se a Game API quiser validar/reservar o pedido antes do pagamento (checar
produto ativo, moeda oficial, elegibilidade `owned`), pode expor este endpoint.
Enquanto não existir, o site usa `GET /store/products/:id` (ao vivo) para obter
**preço e moeda oficiais** no momento do pedido e monta o snapshot — o navegador
nunca informa preço.

---

## 5. Preço e moeda: a Game API é a fonte

- O site **sempre** relê `GET /store/products/:id` (ao vivo, autenticado) para
  capturar `price`/`currency` oficiais no instante da criação do pedido.
- Esse valor é gravado como **snapshot** em `order_items` (centavos + moeda).
- Qualquer preço vindo do formulário/navegador é **ignorado**. Divergência entre
  o preço enviado pelo cliente e o oficial → o oficial prevalece (o pedido usa o
  snapshot da API).

---

## 6. Idempotência — contrato mútuo

| Camada | Chave | Garantia |
| ------ | ----- | -------- |
| Webhook do gateway (site) | `webhook_events.event_id` (UNIQUE) | 10 webhooks idênticos = 1 processamento lógico |
| Fulfillment (site → Game API) | `fulfillments.idempotency_key` (UNIQUE) = `order + ":" + product` | 1 concessão por item, mesmo com N retries |
| Game API | `Idempotency-Key` do header | Deve deduplicar e devolver `idempotent_replay` |

**Teste crítico exigido:** 10 webhooks idênticos + 3 retries de rede devem
resultar em **1 pedido**, **1 pagamento lógico**, **1 fulfillment** e **1
entitlement**. Isso é garantido no site pelas UNIQUE keys acima e verificado nos
testes com `MockHttpClient`/mock de gateway.

---

## 7. Fronteira de responsabilidade (resumo)

| Responsabilidade | Onde vive |
| ---------------- | --------- |
| Catálogo, preço oficial, `owned` | Game API |
| Entitlement/inventário (conceder/revogar) | **Game API (exclusivo)** |
| Pedido, snapshot de preço, transações, webhooks, cupons | Banco do **site** |
| Comunicação com o gateway (Mercado Pago) | **Site** |
| Confirmação de estado do pagamento | **Site** consulta o gateway (nunca confia no payload do webhook) |
| Solicitação de concessão/revogação | **Site** chama a Game API (server-to-server) |

---

## 8. Pendências do lado da Game API (Node)

Para a integração comercial ficar completa de ponta a ponta, o repositório da
Game API precisa implementar:

- [ ] `POST /commerce/fulfillments` (idempotente por `Idempotency-Key`).
- [ ] `GET /commerce/fulfillments/:order_reference`.
- [ ] `POST /commerce/refunds` (revoke/keep, idempotente).
- [ ] Autenticação de **service account** (client/secret dedicados ao site) com
      escopo comercial.
- [ ] (Opcional) `POST /commerce/orders` para reserva/validação.
- [ ] Atualizar `docs/api/API.md` e `openapi.yaml` da Game API com o acima.

Enquanto essas pendências não forem entregues, o site mantém os pagamentos
registrados e os fulfillments em `pending` com retry — **sem conceder itens** —,
e o painel administrativo mostra esses pedidos na **Reconciliação** (pago sem
fulfillment) para reprocessamento assim que os endpoints existirem.
