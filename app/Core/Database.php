<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Camada de acesso ao banco de dados baseada em PDO.
 *
 * Singleton de conexão + métodos utilitários com prepared statements.
 * Toda query parametrizada passa por aqui, garantindo proteção contra
 * SQL Injection.
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Injeta uma instância (usado APENAS em testes, para evitar conexão real).
     * Nunca é chamado em produção.
     */
    public static function setInstance(?Database $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Estabelece a conexão PDO usando config/database.php.
     */
    private function connect(): void
    {
        $cfg = Config::get('database');

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['driver'],
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['password'], $cfg['options']);
        } catch (PDOException $e) {
            Logger::exception($e);
            // Mensagem genérica; detalhes ficam apenas no log.
            throw new \RuntimeException('Não foi possível conectar ao banco de dados.', 0, $e);
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Executa uma query preparada e retorna o statement.
     */
    public function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Retorna a primeira linha (ou null).
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Retorna todas as linhas.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * Retorna um único valor escalar da primeira coluna.
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /**
     * Executa um comando (INSERT/UPDATE/DELETE) e retorna linhas afetadas.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Executa um callback dentro de uma transação.
     *
     * Faz commit se o callback retornar sem exceção; caso contrário, rollback
     * e relança a exceção. Suporta aninhamento simples (reaproveita a transação
     * externa já aberta, sem abrir uma nova).
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function transaction(callable $callback)
    {
        // Se já há uma transação em curso, apenas executa (o controle fica com o chamador externo).
        if ($this->pdo->inTransaction()) {
            return $callback($this);
        }

        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Executa múltiplas instruções SQL (ex.: arquivos .sql de migration/seed).
     * Usado apenas internamente pelo Migrator/instalador.
     */
    public function executeRaw(string $sql): void
    {
        $this->pdo->exec($sql);
    }
}
