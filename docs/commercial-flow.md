# Fluxo comercial (checkout → pagamento → entrega)

Este documento descreve o fluxo comercial **como implementado no site (PHP)**.
A concessão de itens é responsabilidade exclusiva da API do jogo; o site conduz
pedido e pagamento e **solicita** a entrega.

## Visão geral

```
Jogador                Site (PHP)                 Gateway (Mercado Pago)      API do Jogo (Node)
   |                       |                              |                          |
   |  GET /checkout        |                              |                          |
   |---------------------->| ProductPricing.resolve() ----|------------------------->| GET /store/products/:id
   |                       |<-----------------------------|--- preço/moeda oficiais -|
   |  POST /checkout       |                              |                          |
   |---------------------->| cria order (snapshot, cents) |                          |
   |                       | PaymentService.createForOrder|-- cria pagamento ------->|
   |                       |<-----------------------------|-- PIX QR / checkout -----|
   |  paga (PIX/cartão)    |                              |                          |
   |............................................. webhook ......................... |
   |                       | /webhooks/payment/{prov}     |                          |
   |                       | valida assinatura            |                          |
   |                       | consulta estado REAL --------|------------------------->|
   |                       |<-- approved -----------------|                          |
   |                       | GameFulfillmentService ------|------------------------->| POST /commerce/fulfillments
   |                       |<-----------------------------|--- granted --------------|
```

## Passos

1. **Resumo (`GET /checkout?produto=`)** — o site relê o produto na API do jogo
   (ao vivo, autenticado) para obter **preço e moeda oficiais**. O navegador só
   informa o `product_id`, a quantidade e o método; nunca o preço.
2. **Criar pedido (`POST /checkout`)** — exige login. Valida elegibilidade no
   servidor (produto ativo, não `owned`, preço válido), aplica cupom validado no
   backend e grava o pedido em `orders`/`order_items` com **snapshot em
   centavos**. Registra a versão dos Termos de Compra/Reembolso aceitos. Uma
   `idempotency_key` evita pedidos duplicados por duplo clique.
3. **Criar pagamento** — `PaymentService::createForOrder` chama o gateway
   (server-side) e devolve os dados de pagamento (PIX copia-e-cola/QR ou
   retorno do cartão). Nenhuma aprovação é simulada.
4. **Pagamento** — o jogador paga no PIX ou no checkout do cartão (tokenização
   feita pelo gateway; o site nunca recebe o número do cartão).
5. **Webhook** — o gateway notifica `/webhooks/payment/{provider}`. O site
   valida a assinatura, é idempotente por `event_id` e **consulta o gateway**
   para o estado real. Nunca trata o payload como prova.
6. **Entrega** — só quando o pagamento está **realmente aprovado**, o site
   solicita a concessão à API do jogo via `GameFulfillmentService` (idempotente).
   A entrega é confirmada pela API do jogo — o site não concede nada localmente.

## Estados

O pedido tem três estados independentes:

- `order_status`: pending, awaiting_payment, paid, cancelled, refunded, failed…
- `payment_status`: none, pending, approved, rejected, refunded, charged_back…
- `fulfillment_status`: none, pending, processing, fulfilled, failed, revoked.

Um pedido só é "concluído" para o jogador quando `fulfillment_status = fulfilled`.

## Recuperação

A página de pagamento faz *polling* de `/checkout/{ref}/status`, que consulta o
gateway. Se o jogador fechar a aba, o estado real continua correto: ao abrir o
pedido em `/conta/pedidos/{ref}`, o site reconsulta o gateway antes de exibir.
