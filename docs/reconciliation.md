# Reconciliação

Reconciliação identifica **divergências entre pagamento e entrega**: pedidos
cujo pagamento foi aprovado, mas cuja concessão de itens ainda não foi
concluída pela API do jogo.

## Onde

Admin → Loja → **Reconciliação** (permissão `store.reconciliation`).

A lista vem de `Order::paidWithoutFulfillment()`:

```sql
SELECT * FROM orders
WHERE payment_status = 'approved'
  AND fulfillment_status IN ('none','pending','processing','failed')
```

## Por que acontece

- A API do jogo estava indisponível quando o webhook chegou (fulfillment ficou
  `pending`, aguardando retry).
- O endpoint `/commerce/fulfillments` ainda não foi implementado/configurado
  (ver `docs/api/commercial-integration.md`).
- Falha definitiva na concessão (`failed`) que exige análise manual.

## Como resolver

1. Abrir o pedido e revisar a timeline (`order_events`) e os `fulfillments`.
2. **Reprocessar entrega**: solicita novamente a concessão à API do jogo. A
   operação é idempotente — se o item já foi concedido, não duplica.
3. Se a falha for definitiva (produto/jogador inexistente), corrigir a causa na
   API do jogo antes de reprocessar.

## Garantias

- Reprocessar nunca concede localmente e nunca duplica (idempotência por
  `referência:product_id`).
- O pagamento não é alterado pela reconciliação; apenas a entrega é reprocessada.
- Toda ação de reprocessamento/refund é registrada em auditoria
  (usuário, ação, pedido).

## Métricas

O dashboard da Loja mostra o total de pedidos "pago sem entrega" e de entregas
com falha, para monitoramento contínuo.
