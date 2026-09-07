<?php

namespace App\Core;

/**
 * Sistema próprio de migrations.
 *
 * - Lê arquivos .sql de database/migrations em ordem alfabética/numérica.
 * - Executa apenas as pendentes (não registradas na tabela `migrations`).
 * - Registra cada execução para nunca rodar a mesma migration duas vezes.
 *
 * REGRA: arquivos de migration são imutáveis. Qualquer alteração de schema
 * deve ser feita criando uma NOVA migration.
 */
class Migrator
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Garante a existência da tabela de controle (executa a migration 001
     * diretamente caso ainda não exista).
     */
    private function ensureMigrationsTable(): void
    {
        $exists = $this->db->fetchColumn("SHOW TABLES LIKE 'migrations'");
        if ($exists) {
            return;
        }

        $bootstrap = MIGRATIONS_PATH . '/001_create_migrations_table.sql';
        if (is_file($bootstrap)) {
            $this->db->executeRaw(file_get_contents($bootstrap));
            $this->markAsRun('001_create_migrations_table.sql', 1);
        }
    }

    /**
     * Lista os nomes de migrations já executadas.
     */
    public function executed(): array
    {
        $this->ensureMigrationsTable();
        $rows = $this->db->fetchAll("SELECT `migration` FROM `migrations`");
        return array_column($rows, 'migration');
    }

    /**
     * Lista os arquivos de migration disponíveis, ordenados.
     */
    public function available(): array
    {
        $files = glob(MIGRATIONS_PATH . '/*.sql') ?: [];
        $names = array_map('basename', $files);
        sort($names, SORT_STRING);
        return $names;
    }

    /**
     * Retorna migrations ainda não executadas.
     */
    public function pending(): array
    {
        $executed = $this->executed();
        return array_values(array_diff($this->available(), $executed));
    }

    /**
     * Executa todas as migrations pendentes.
     *
     * @return array<int,string> Lista de migrations executadas nesta chamada.
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $pending = $this->pending();
        $ran = [];
        $batch = $this->nextBatch();

        foreach ($pending as $migration) {
            $sql = file_get_contents(MIGRATIONS_PATH . '/' . $migration);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                $this->db->executeRaw($sql);
                $this->markAsRun($migration, $batch);
                $ran[] = $migration;
            } catch (\Throwable $e) {
                Logger::error("Falha na migration {$migration}: " . $e->getMessage());
                throw new \RuntimeException("Erro ao executar migration {$migration}: " . $e->getMessage(), 0, $e);
            }
        }

        return $ran;
    }

    /**
     * Executa os arquivos de seed (idempotentes por natureza).
     *
     * @return array<int,string> Seeds executados.
     */
    public function seed(): array
    {
        $files = glob(SEEDS_PATH . '/*.sql') ?: [];
        $names = array_map('basename', $files);
        sort($names, SORT_STRING);

        $ran = [];
        foreach ($names as $seed) {
            $sql = file_get_contents(SEEDS_PATH . '/' . $seed);
            if ($sql === false || trim($sql) === '') {
                continue;
            }
            try {
                $this->db->executeRaw($sql);
                $ran[] = $seed;
            } catch (\Throwable $e) {
                Logger::error("Falha no seed {$seed}: " . $e->getMessage());
                throw new \RuntimeException("Erro ao executar seed {$seed}: " . $e->getMessage(), 0, $e);
            }
        }

        return $ran;
    }

    private function nextBatch(): int
    {
        $max = $this->db->fetchColumn("SELECT MAX(`batch`) FROM `migrations`");
        return ((int) $max) + 1;
    }

    private function markAsRun(string $migration, int $batch): void
    {
        $this->db->execute(
            "INSERT INTO `migrations` (`migration`, `batch`, `status`) VALUES (:m, :b, 'success')
             ON DUPLICATE KEY UPDATE `batch` = VALUES(`batch`)",
            ['m' => $migration, 'b' => $batch]
        );
    }
}
