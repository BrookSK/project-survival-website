# Fulfillment (concessão de itens)

A **concessão de itens é exclusiva da API do jogo**. O site nunca insere,
concede ou remove entitlement/inventário em banco próprio. Ele apenas registra a
INTENÇÃO/RESULTADO da entrega e **solicita** a concessão à API do jogo.

## Adapter

`app/Services/Commerce/GameFulfillmentService.php` fala com a API do jogo usando
uma **service account** (client/secret configurados no painel), server-to-server:

```
POST {base_url}/commerce/fulfillments
X-Client-Id: <game_api_service_client_id>
Authorization: Bearer <game_api_service_secret>
Idempotency-Key: <referência>:<product_id>
```

> O endpoint `/commerce/fulfillments` faz parte do **contrato** documentado em
> `docs/api/commercial-integration.md` e ainda precisa ser implementado na API
> do jogo. Enquanto não existir/estiver configurado, o fulfillment permanece
> `pending` (com retry) — nunca é marcado como entregue de forma fictícia.

## Idempotência

- Chave: `idempotency_key = "<referência do pedido>:<product_id>"`.
- No banco: `fulfillments.idempotency_key` é **UNIQUE** — uma concessão por item.
- No header: `Idempotency-Key` permite à API do jogo deduplicar reenvios.
- Resultado: 1 concessão por item, mesmo com N webhooks/retries.

## Estados e retry

`fulfillments.status`: `pending` → `processing` → `fulfilled` | `failed` | `revoked`.

- **Sucesso** (`granted`/`fulfilled`): marca `fulfilled`.
- **Assíncrono** (`accepted`/`processing`): marca `processing` (reconsultar).
- **Transitório** (timeout, 5xx, 429, API off): mantém `pending` e agenda retry
  com **backoff exponencial** (30s·2^(n-1), teto 1h), respeitando
  `max_attempts` (config `fulfillment_max_attempts`).
- **Definitivo** (400/401/403/404/409/422): marca `failed` e **não** faz retry;
  aparece para o admin resolver.

## Reprocessamento

- **Automático**: pedidos com fulfillment devido a retry podem ser reprocessados
  (ver `Fulfillment::dueForRetry`).
- **Manual**: Admin → Loja → Pedido → "Reprocessar entrega" (auditado). Só para
  pedidos com pagamento **aprovado**.

## Após a entrega

Quando um pedido fica `fulfilled`, o site invalida o cache do catálogo
(`GameStoreService::flushCache`) para que `owned` seja reconsultado ao vivo na
API do jogo. O inventário do jogador nunca é servido de cache após a compra.
