<?php

namespace App\Core;

/**
 * Model base.
 *
 * Fornece operações CRUD comuns sobre uma tabela, sempre com prepared
 * statements. Models concretos definem $table, $primaryKey e $fillable.
 *
 * Nomes de tabela e colunas usados na construção de SQL vêm de propriedades
 * definidas no código (nunca de entrada do usuário), evitando SQL Injection
 * por identificadores.
 */
abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    /** @var string[] Colunas que podem ser preenchidas em massa */
    protected array $fillable = [];

    /**
     * Habilita soft delete. Quando true, delete() marca `deleted_at` em vez de
     * remover fisicamente, e as consultas base ignoram registros excluídos.
     */
    protected bool $softDeletes = false;

    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Executa um callback dentro de uma transação (atalho para os models).
     */
    public function transaction(callable $callback)
    {
        return $this->db->transaction($callback);
    }

    /**
     * Busca um registro pela chave primária.
     */
    public function find($id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Busca um registro pela primeira ocorrência de uma coluna.
     */
    public function findBy(string $column, $value): ?array
    {
        $column = $this->assertColumn($column);
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` = :value LIMIT 1";
        return $this->db->fetch($sql, ['value' => $value]);
    }

    /**
     * Retorna todos os registros, com ordenação opcional.
     */
    public function all(?string $orderBy = null, string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy !== null) {
            $orderBy = $this->assertColumn($orderBy);
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * Conta registros, com condições WHERE opcionais (coluna => valor, igualdade).
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        $params = [];
        if ($conditions) {
            $sql .= ' WHERE ' . $this->buildWhere($conditions, $params);
        }
        return (int) $this->db->fetchColumn($sql, $params);
    }

    /**
     * Insere um registro (apenas colunas fillable) e retorna o ID.
     */
    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        if (!$data) {
            throw new \InvalidArgumentException('Nenhum dado válido para inserir.');
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $cols = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

        $sql = "INSERT INTO `{$this->table}` ({$cols}) VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->execute($sql, $data);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza um registro pela chave primária (apenas colunas fillable).
     */
    public function update($id, array $data): int
    {
        $data = $this->filterFillable($data);
        if (!$data) {
            return 0;
        }

        $sets = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($data)));
        $data['__pk'] = $id;

        $sql = "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = :__pk";
        return $this->db->execute($sql, $data);
    }

    /**
     * Remove um registro pela chave primária.
     * Com soft delete habilitado, marca `deleted_at` em vez de excluir.
     */
    public function delete($id): int
    {
        if ($this->softDeletes) {
            $sql = "UPDATE `{$this->table}` SET `deleted_at` = NOW() WHERE `{$this->primaryKey}` = :id AND `deleted_at` IS NULL";
            return $this->db->execute($sql, ['id' => $id]);
        }
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return $this->db->execute($sql, ['id' => $id]);
    }

    /**
     * Remove definitivamente um registro (ignora soft delete).
     */
    public function forceDelete($id): int
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return $this->db->execute($sql, ['id' => $id]);
    }

    /**
     * Restaura um registro soft-deleted.
     */
    public function restore($id): int
    {
        if (!$this->softDeletes) {
            return 0;
        }
        $sql = "UPDATE `{$this->table}` SET `deleted_at` = NULL WHERE `{$this->primaryKey}` = :id";
        return $this->db->execute($sql, ['id' => $id]);
    }

    /**
     * Cláusula que exclui registros soft-deleted, quando aplicável.
     * Retorna algo como " AND `deleted_at` IS NULL" para compor consultas.
     */
    protected function notDeletedClause(string $alias = ''): string
    {
        if (!$this->softDeletes) {
            return '';
        }
        $col = $alias !== '' ? "{$alias}.`deleted_at`" : "`deleted_at`";
        return " {$col} IS NULL";
    }

    /**
     * Retorna a instância de Database para consultas customizadas nos models filhos.
     */
    protected function db(): Database
    {
        return $this->db;
    }

    /**
     * Mantém apenas as chaves presentes em $fillable.
     */
    protected function filterFillable(array $data): array
    {
        if (!$this->fillable) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Garante que a coluna informada é conhecida (fillable, PK ou timestamps),
     * evitando injeção de identificadores em ORDER BY / WHERE dinâmicos.
     */
    protected function assertColumn(string $column): string
    {
        $allowed = array_merge($this->fillable, [$this->primaryKey, 'created_at', 'updated_at']);
        if (in_array($column, $allowed, true)) {
            return $column;
        }
        // Fallback conservador: só permite identificadores simples.
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            return $column;
        }
        throw new \InvalidArgumentException('Coluna inválida.');
    }

    /**
     * Constrói uma cláusula WHERE de igualdades (AND) com parâmetros nomeados.
     */
    protected function buildWhere(array $conditions, array &$params): string
    {
        $clauses = [];
        foreach ($conditions as $column => $value) {
            $column = $this->assertColumn($column);
            $ph = 'w_' . $column;
            $clauses[] = "`{$column}` = :{$ph}";
            $params[$ph] = $value;
        }
        return implode(' AND ', $clauses);
    }
}
