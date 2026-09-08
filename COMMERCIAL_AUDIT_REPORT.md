# Relatório de Auditoria Comercial

**Projeto:** Website Project Survival (PHP/MVC)
**Versão:** 2.3.0
**Data:** 2026-09-07
**Escopo:** Camada comercial ponta a ponta no lado do site (checkout,
pagamentos, pedidos, webhooks, entrega via API do jogo, admin, reconciliação,
reembolso).

> **Escopo de implementação (Opção B).** O repositório da API do jogo (Node)
> **não** faz parte deste workspace. Tudo que é legítimo no lado do site foi
> implementado e testado. A concessão de itens depende de endpoints da API do
> jogo que ainda **não existem**; eles estão **especificados** em
> `docs/api/commercial-integration.md` e são **mockados** nos testes. Nada foi
> marcado como "pronto" sem ser uma operação real ou um teste explicitamente
> mockado.

## 1. Regras invioláveis (e como são garantidas)

| Regra | Garantia no código |
| ----- | ------------------ |
| Não simular pagamento | Status vem sempre de `MercadoPagoGateway::getPayment` (gateway = fonte de verdade). `NullGateway` recusa; nunca aprova. |
| Não simular entitlement | O site nunca escreve entitlement. Só `GameFulfillmentService` solicita à API do jogo. |
| Não simular fulfillment | Sem API/config, o fulfillment fica `pending` (retry). Nunca é marcado `fulfilled` sem resposta real. |
| Não conceder itens pelo PHP | Não há INSERT de entitlement/inventário no site. |
| Não confiar no navegador para valores/status | `ProductPricing` relê preço/moeda na API do jogo; webhook consulta o gateway. |
| Server → Server → confirmação | Checkout/refund/entrega são server-side; concessão é service-to-service. |

## 2. Arquitetura

- **Pagamentos** (`app/Services/Payments`): `PaymentGatewayInterface`,
  `MercadoPagoGateway`, `NullGateway`, `PaymentGatewayManager`, `PaymentService`,
  DTOs (`PaymentIntent`, `RefundResult`, `WebhookResult`), `PaymentStatus`,
  `PaymentConfig`, `PaymentException`.
- **Comércio** (`app/Services/Commerce`): `ProductPricing`, `CheckoutService`,
  `GameFulfillmentService`, `WebhookProcessor`, `OrderPresenter`,
  `FulfillmentOutcome`, `PricingException`.
- **Persistência** (migração `027`): `orders`, `order_items`,
  `payment_transactions`, `webhook_events`, `order_events`, `fulfillments`,
  `coupons`, `coupon_redemptions`. Dinheiro em **centavos** (inteiro).
- **Controllers**: site `CheckoutController`, `OrderController`,
  `PaymentWebhookController`; admin `StoreController`, `CouponController`.

## 3. Endpoints (site)

| Rota | Auth | Uso |
| ---- | ---- | --- |
| `GET /checkout` | jogador | Resumo com preço oficial |
| `POST /checkout` | jogador | Cria pedido + pagamento |
| `GET /checkout/{ref}/pagamento` | jogador | Instruções PIX/cartão |
| `GET /checkout/{ref}/status` | jogador | Estado real (consulta gateway) |
| `GET /conta/pedidos` | jogador | Lista de pedidos |
| `GET /conta/pedidos/{ref}` | jogador | Detalhe + timeline (anti-IDOR) |
| `POST /webhooks/payment/{provider}` | nenhuma (assinatura) | Webhook do gateway |
| `GET /admin/loja/*` | admin (`store.*`) | Painel comercial |

## 4. Fluxo pagamento → webhook → entrega

1. Pedido criado com snapshot de preço (centavos) **antes** do pagamento.
2. Pagamento criado no gateway (PIX/cartão hospedado).
3. Webhook recebido → assinatura validada → idempotência por `event_id` →
   **consulta ao gateway** para estado real.
4. Se **aprovado**, `GameFulfillmentService` solicita a concessão (idempotente)
   à API do jogo. Caso contrário/indisponível: `pending` com retry.

## 5. Idempotência

| Camada | Chave (UNIQUE) |
| ------ | -------------- |
| Checkout (anti-duplo-clique) | `orders.idempotency_key` |
| Webhook | `webhook_events(provider, event_id)` |
| Transação | `payment_transactions(gateway, external_id)` |
| Fulfillment | `fulfillments.idempotency_key` = `referência:product_id` |
| Cupom por pedido | `coupon_redemptions.order_id` |

**Teste crítico coberto:** 10 webhooks idênticos + 3 retries → **1 pedido, 1
pagamento lógico, 1 fulfillment, 1 entitlement** (`WebhookCriticalTest`).

## 6. Segurança

- Webhook fora de auth/CSRF, protegido por **assinatura HMAC-SHA256**;
  assinatura inválida → 401 e não processa.
- Anti-IDOR: pedidos do jogador filtrados por `player_id` da sessão; acesso a
  pedido alheio → 404.
- Cartão tokenizado pelo gateway; o site nunca recebe/armazena PAN.
- Segredos nunca exibidos/logados; `HttpClient` sanitiza headers sensíveis.
- `store.refunds` não é concedido por padrão (operação de maior risco).
- Toda ação sensível (refund, reprocessar) é auditada.

## 7. Reembolso e chargeback

- Refund chama o gateway (`PaymentService::refund`) — nunca só muda status
  local. Conforme a política (`revoke`/`keep`), solicita revogação à API do jogo
  (`GameFulfillmentService::revokeOrder`, idempotente).
- Chargeback recebido via webhook reflete o estado real (consulta ao gateway) e
  registra na timeline.

## 8. Testes

Suíte: **78 testes, 0 falhas** (`php tests/run.php`). Comerciais:
`PaymentStatusTest`, `MercadoPagoWebhookTest` (assinatura válida/adulterada/
ausente/outro pagamento), `CouponDiscountTest`, `ProductPricingTest` (preço
oficial, owned bloqueado, preço inválido), `FulfillmentIdempotencyTest`
(sucesso, reenvio não duplica, API offline → pending, falha definitiva → sem
retry, sem config → pending sem chamar), `WebhookCriticalTest` (teste crítico,
assinatura inválida, transação desconhecida), `RefundAndPresenterTest`
(refund via gateway, chargeback, formatação). Tudo mockado (gateway e API do
jogo); sem tocar em serviços reais.

## 9. Pendências (lado da API do jogo — Node)

Para a integração ficar completa de ponta a ponta:

- [ ] `POST /commerce/fulfillments` (idempotente).
- [ ] `GET /commerce/fulfillments/:order_reference`.
- [ ] `POST /commerce/refunds` (revoke/keep, idempotente).
- [ ] Autenticação de service account com escopo comercial.
- [ ] Atualizar `API.md`/`openapi.yaml` da API do jogo com o contrato.

Enquanto isso, o site opera com segurança: registra pagamentos, mantém entrega
`pending` com retry e expõe os casos na Reconciliação. **Nada é concedido de
forma fictícia.**

## 10. Configuração necessária (painel, sem `.env`)

Admin → Configurações → **Pagamentos / Loja**: habilitar a loja, escolher o
gateway (`mercadopago`), ambiente, moeda, chaves do Mercado Pago e a service
account da API do jogo. Segredos são mascarados. Ver `docs/payment-architecture.md`.
