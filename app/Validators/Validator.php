<?php

namespace App\Validators;

use App\Core\Database;

/**
 * Camada de validação reutilizável.
 *
 * Uso:
 *   $v = new Validator($data, [
 *       'name'  => 'required|string|max:150',
 *       'email' => 'required|email|unique:users,email',
 *   ]);
 *   if ($v->fails()) { $errors = $v->errors(); }
 *
 * Regras suportadas: required, string, integer, email, url, min:N, max:N,
 * confirmed, in:a,b,c, unique:tabela,coluna[,ignoreId], boolean, slug.
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    /** Rótulos amigáveis por campo (opcional) */
    private array $labels;

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->labels = $labels;
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            // Se não é obrigatório e está vazio, pula as demais regras
            $isRequired = in_array('required', $rules, true);
            $isEmpty = $value === null || $value === '' || (is_array($value) && count($value) === 0);

            if (!$isRequired && $isEmpty) {
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $name, $param, $value);
            }
        }
    }

    private function applyRule(string $field, string $rule, ?string $param, $value): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->addError($field, 'O campo :field é obrigatório.');
                }
                break;

            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, 'O campo :field deve ser um texto.');
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'O campo :field deve ser um número inteiro.');
                }
                break;

            case 'boolean':
                if (!in_array($value, ['0', '1', 0, 1, true, false, 'true', 'false'], true)) {
                    $this->addError($field, 'O campo :field deve ser verdadeiro ou falso.');
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Informe um e-mail válido.');
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'Informe uma URL válida.');
                }
                break;

            case 'slug':
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value)) {
                    $this->addError($field, 'O campo :field deve conter apenas letras minúsculas, números e hífens.');
                }
                break;

            case 'min':
                if (self::length((string) $value) < (int) $param) {
                    $this->addError($field, "O campo :field deve ter no mínimo {$param} caracteres.");
                }
                break;

            case 'max':
                if (self::length((string) $value) > (int) $param) {
                    $this->addError($field, "O campo :field deve ter no máximo {$param} caracteres.");
                }
                break;

            case 'in':
                $options = explode(',', (string) $param);
                if (!in_array((string) $value, $options, true)) {
                    $this->addError($field, 'Valor inválido para :field.');
                }
                break;

            case 'confirmed':
                $confirmation = $this->data[$field . '_confirmation'] ?? null;
                if ($value !== $confirmation) {
                    $this->addError($field, 'A confirmação de :field não confere.');
                }
                break;

            case 'unique':
                // unique:tabela,coluna[,ignoreId]
                $parts = explode(',', (string) $param);
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? $field;
                $ignoreId = $parts[2] ?? null;
                if ($this->existsInDb($table, $column, $value, $ignoreId)) {
                    $this->addError($field, 'Este valor de :field já está em uso.');
                }
                break;
        }
    }

    private function existsInDb(string $table, string $column, $value, $ignoreId): bool
    {
        // Whitelist de identificadores para evitar injeção via nome de tabela/coluna
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table) ||
            !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value";
        $params = ['value' => $value];

        if ($ignoreId !== null && $ignoreId !== '') {
            $sql .= " AND `id` <> :ignore";
            $params['ignore'] = $ignoreId;
        }

        try {
            return (int) Database::getInstance()->fetchColumn($sql, $params) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function addError(string $field, string $message): void
    {
        // Apenas o primeiro erro por campo
        if (isset($this->errors[$field])) {
            return;
        }
        $label = $this->labels[$field] ?? $field;
        $this->errors[$field] = str_replace(':field', $label, $message);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
