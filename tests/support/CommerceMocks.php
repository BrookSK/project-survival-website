<?php

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentTransaction;
use App\Models\WebhookEvent;

/**
 * Mocks in-memory dos Models comerciais. Estendem os Models reais mas guardam
 * tudo em arrays PHP, simulando as UNIQUE keys (idempotência) sem tocar no
 * banco. Assim conseguimos provar a lógica de idempotência (10 webhooks + 3
 * retries = 1 de cada) sem DB real.
 *
 * Requer que Database::setInstance(new StubDatabase()) tenha sido chamado antes
 * de instanciar (para o construtor do Model não conectar).
 */

class FakeOrder extends Order
{
    /** @var array<int,array> */
    public array $rows = [];
    private int $seq = 0;

    public function seed(array $row): int
    {
        $id = ++$this->seq;
        $row['id'] = $id;
        $this->rows[$id] = $row;
        return $id;
    }

    public function find($id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByReference(string $reference): ?array
    {
        foreach ($this->rows as $r) {
            if (($r['reference'] ?? null) === $reference) {
                return $r;
            }
        }
        return null;
    }

    public function items(int $orderId): array
    {
        return $this->rows[$orderId]['__items'] ?? [];
    }

    public function updateStates(int $orderId, array $states): int
    {
        if (!isset($this->rows[$orderId])) {
            return 0;
        }
        foreach (['order_status', 'payment_status', 'fulfillment_status', 'paid_at'] as $c) {
            if (array_key_exists($c, $states)) {
                $this->rows[$orderId][$c] = $states[$c];
            }
        }
        return 1;
    }
}

class FakeWebhookEvent extends WebhookEvent
{
    /** @var array<string,array> key = provider|event_id */
    public array $rows = [];
    private int $seq = 0;

    public function registerArrival(string $provider, string $eventId, ?string $eventType, ?string $externalRef, bool $signatureValid, ?string $payloadJson): array
    {
        $key = $provider . '|' . $eventId;
        if (isset($this->rows[$key])) {
            return ['id' => $this->rows[$key]['id'], 'duplicate' => true];
        }
        $id = ++$this->seq;
        $this->rows[$key] = [
            'id' => $id, 'provider' => $provider, 'event_id' => $eventId,
            'status' => 'received', 'signature_valid' => $signatureValid ? 1 : 0,
        ];
        return ['id' => $id, 'duplicate' => false];
    }

    public function markProcessed(int $id, string $status, ?int $orderId = null, ?string $note = null): int
    {
        foreach ($this->rows as &$r) {
            if ($r['id'] === $id) {
                $r['status'] = $status;
                return 1;
            }
        }
        return 0;
    }

    public function countAll(): int
    {
        return count($this->rows);
    }
}

class FakeFulfillment extends Fulfillment
{
    /** @var array<string,array> key = idempotency_key */
    public array $rows = [];
    private int $seq = 0;

    public function ensure(array $data): array
    {
        $key = $data['idempotency_key'];
        if (isset($this->rows[$key])) {
            return ['id' => $this->rows[$key]['id'], 'created' => false];
        }
        $id = ++$this->seq;
        $this->rows[$key] = [
            'id' => $id, 'idempotency_key' => $key, 'status' => 'pending',
            'attempts' => 0, 'max_attempts' => $data['max_attempts'] ?? 8,
            'permanent_failure' => 0, 'product_id' => $data['product_id'] ?? '',
        ];
        return ['id' => $id, 'created' => true];
    }

    public function find($id): ?array
    {
        foreach ($this->rows as $r) {
            if ($r['id'] === (int) $id) {
                return $r;
            }
        }
        return null;
    }

    public function findByKey(string $key): ?array
    {
        return $this->rows[$key] ?? null;
    }

    private function &byId(int $id): array
    {
        foreach ($this->rows as &$r) {
            if ($r['id'] === $id) {
                return $r;
            }
        }
        $null = [];
        return $null;
    }

    public function markFulfilled(int $id, ?string $externalId = null): int
    {
        $r = &$this->byId($id);
        if ($r) {
            $r['status'] = 'fulfilled';
            $r['external_id'] = $externalId;
        }
        return 1;
    }

    public function markFailure(int $id, string $error, bool $permanent, ?string $nextAttemptAt): int
    {
        $r = &$this->byId($id);
        if ($r) {
            $r['attempts']++;
            $r['status'] = $permanent ? 'failed' : 'pending';
            $r['permanent_failure'] = $permanent ? 1 : 0;
            $r['last_error'] = $error;
        }
        return 1;
    }

    public function markProcessing(int $id): int
    {
        return 1;
    }

    public function markRevoked(int $id, ?string $externalId = null): int
    {
        $r = &$this->byId($id);
        if ($r) {
            $r['status'] = 'revoked';
        }
        return 1;
    }

    public function countFulfilled(): int
    {
        return count(array_filter($this->rows, static fn ($r) => $r['status'] === 'fulfilled'));
    }

    public function countAll(): int
    {
        return count($this->rows);
    }
}

class FakePaymentTransaction extends PaymentTransaction
{
    /** @var array<string,array> key = gateway|external_id */
    public array $rows = [];
    private int $seq = 0;

    public function findByExternal(string $gateway, string $externalId): ?array
    {
        return $this->rows[$gateway . '|' . $externalId] ?? null;
    }

    public function forOrder(int $orderId): array
    {
        return array_values(array_filter($this->rows, static fn ($r) => (int) $r['order_id'] === $orderId));
    }

    public function upsertByExternal(array $data): int
    {
        $key = $data['gateway'] . '|' . ($data['external_id'] ?? '');
        if (isset($this->rows[$key])) {
            $this->rows[$key] = array_merge($this->rows[$key], $data);
            return $this->rows[$key]['id'];
        }
        $id = ++$this->seq;
        $data['id'] = $id;
        $data['refunded_cents'] = $data['refunded_cents'] ?? 0;
        $this->rows[$key] = $data;
        return $id;
    }

    public function updateStatus(int $id, string $status, ?int $refundedCents = null): int
    {
        foreach ($this->rows as &$r) {
            if ($r['id'] === $id) {
                $r['status'] = $status;
                if ($refundedCents !== null) {
                    $r['refunded_cents'] = $refundedCents;
                }
                return 1;
            }
        }
        return 0;
    }

    public function seed(array $row): int
    {
        $id = ++$this->seq;
        $row['id'] = $id;
        $row['refunded_cents'] = $row['refunded_cents'] ?? 0;
        $this->rows[$row['gateway'] . '|' . ($row['external_id'] ?? '')] = $row;
        return $id;
    }
}

class FakeOrderEvent extends OrderEvent
{
    /** @var array<int,array> */
    public array $rows = [];

    public function record(int $orderId, string $type, ?string $message = null, string $actor = 'system', array $data = []): int
    {
        $this->rows[] = ['order_id' => $orderId, 'type' => $type, 'message' => $message, 'actor' => $actor];
        return count($this->rows);
    }

    public function forOrder(int $orderId): array
    {
        return array_values(array_filter($this->rows, static fn ($r) => $r['order_id'] === $orderId));
    }

    public function countOfType(string $type): int
    {
        return count(array_filter($this->rows, static fn ($r) => $r['type'] === $type));
    }
}
