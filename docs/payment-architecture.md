# Arquitetura de pagamentos

A camada de pagamentos vive em `app/Services/Payments/` e é isolada por trás de
uma interface, de modo que o restante do sistema não conhece detalhes do
provedor.

## Componentes

- **`PaymentGatewayInterface`** — contrato: `createPayment`, `getPayment`,
  `refundPayment`, `parseWebhook`. Toda comunicação é server-side.
- **`MercadoPagoGateway`** — implementação para o Mercado Pago:
  - PIX: `POST /v1/payments` com `payment_method_id=pix`; devolve
    `point_of_interaction.transaction_data` (QR e copia-e-cola).
  - Cartão: usa `token` gerado no navegador pelo SDK do gateway. **O site nunca
    recebe nem armazena o número do cartão** (conformidade PCI: SAQ A).
  - `getPayment` (`GET /v1/payments/:id`) é a **fonte de verdade** do status.
  - `refundPayment` (`POST /v1/payments/:id/refunds`), com `Idempotency-Key`.
  - `parseWebhook` valida a assinatura HMAC-SHA256 (`x-signature: ts=…,v1=…`)
    sobre o template `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`.
- **`NullGateway`** — usado quando a loja está desativada ou nenhum provedor foi
  configurado. Recusa de forma segura; nunca simula aprovação.
- **`PaymentGatewayManager`** — fábrica que resolve o gateway ativo a partir das
  configurações (`payment_gateway`, `payments_enabled`).
- **`PaymentService`** — orquestra o gateway com a persistência
  (`payment_transactions`, `orders`, `order_events`). **Não concede itens.**

## Dinheiro

Todos os valores são **inteiros em centavos**. A conversão para/da unidade
monetária do gateway acontece só na borda (`amount = cents / 100`).

## Configuração (sem `.env`)

Grupo `payments` (Admin → Configurações → Pagamentos / Loja):

| Chave | Secreto | Uso |
| ----- | ------- | --- |
| `payments_enabled` | não | Liga o checkout |
| `payment_gateway` | não | `mercadopago` ou `null` |
| `payment_environment` | não | `sandbox` / `production` |
| `payment_currency` | não | Moeda de cobrança (ISO 4217) |
| `mercadopago_public_key` | não | Tokenização no navegador |
| `mercadopago_access_token` | **sim** | Chamadas server-side |
| `mercadopago_webhook_secret` | **sim** | Validação de assinatura |

Segredos (`is_secret = 1`) nunca são exibidos na UI; se enviados vazios, o valor
atual é mantido. Nenhum segredo aparece em log (o `HttpClient` sanitiza headers
sensíveis).

## Erros

`PaymentException` distingue falhas **transitórias** (`retryable = true`:
timeout, 5xx, 429) de **definitivas** (`retryable = false`: 400/401/403/4xx).
Isso alimenta a decisão de retry sem simular sucesso.

## Segurança do webhook

- Rota fora de autenticação/CSRF (o chamador é o gateway).
- Assinatura inválida → resposta 401, evento registrado como `invalid`,
  processamento não ocorre.
- Idempotência por `webhook_events(provider, event_id)` UNIQUE.
- O estado real vem sempre de `getPayment` — o payload é apenas um gatilho.
