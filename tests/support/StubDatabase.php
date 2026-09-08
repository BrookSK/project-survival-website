<?php

use App\Core\Database;

/**
 * Database de teste que NÃO conecta ao banco. Serve apenas para satisfazer o
 * construtor de Model (que faz Database::getInstance()) sem tocar em PDO.
 *
 * Os mocks de Model sobrescrevem todos os métodos que usariam o banco, então
 * estes stubs nunca são realmente chamados; se forem, lançam para deixar claro
 * que faltou mockar algo. Vive apenas em tests/.
 */
class StubDatabase extends Database
{
    /** @var array<int,array{group:string,key:string,value:string,type:string}> */
    private array $settings = [];

    // Construtor público vazio: não chama o pai (que conectaria via PDO).
    public function __construct()
    {
    }

    /**
     * Programa linhas de `settings` para o SettingsService ler nos testes.
     *
     * @param array<string,array{value:string,type?:string,group?:string}> $rows key => spec
     */
    public function setSettings(array $rows): void
    {
        $this->settings = [];
        foreach ($rows as $key => $spec) {
            $this->settings[] = [
                'group' => $spec['group'] ?? 'payments',
                'key'   => $key,
                'value' => (string) $spec['value'],
                'type'  => $spec['type'] ?? 'string',
            ];
        }
    }

    private function fail(string $method): void
    {
        throw new \RuntimeException("StubDatabase::{$method} chamado — o teste deveria ter mockado o Model.");
    }

    public function run(string $sql, array $params = []): \PDOStatement
    {
        $this->fail('run');
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $this->fail('fetch');
        return null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        // Serve as settings programadas (usado pelo SettingsService::loadAll).
        if (stripos($sql, 'FROM `settings`') !== false) {
            return $this->settings;
        }
        $this->fail('fetchAll');
        return [];
    }

    public function fetchColumn(string $sql, array $params = [])
    {
        $this->fail('fetchColumn');
    }

    public function execute(string $sql, array $params = []): int
    {
        $this->fail('execute');
        return 0;
    }

    public function lastInsertId(): string
    {
        $this->fail('lastInsertId');
        return '0';
    }

    public function transaction(callable $callback)
    {
        // Executa o callback direto (sem transação real) para testes de serviço.
        return $callback($this);
    }

    public function inTransaction(): bool
    {
        return false;
    }
}
