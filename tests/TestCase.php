<?php

/**
 * Micro-framework de asserções para testes de unidade.
 *
 * Sem dependências externas (não requer PHPUnit/Composer). Cada arquivo de
 * teste estende esta classe e define métodos públicos que começam com "test".
 * O runner (tests/run.php) descobre e executa esses métodos.
 */
abstract class TestCase
{
    private array $failures = [];
    private int $assertions = 0;

    /**
     * Executa todos os métodos test* e retorna os resultados.
     *
     * @return array{class:string,passed:int,failed:int,failures:string[],assertions:int}
     */
    public function run(): array
    {
        $passed = 0;
        $failed = 0;
        $failures = [];

        $methods = array_filter(get_class_methods($this), static function ($m) {
            return strncmp($m, 'test', 4) === 0;
        });

        foreach ($methods as $method) {
            $this->failures = [];
            try {
                $this->$method();
                if ($this->failures === []) {
                    $passed++;
                } else {
                    $failed++;
                    foreach ($this->failures as $f) {
                        $failures[] = static::class . '::' . $method . ' — ' . $f;
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                $failures[] = static::class . '::' . $method . ' — exceção: ' . $e->getMessage();
            }
        }

        return [
            'class'      => static::class,
            'passed'     => $passed,
            'failed'     => $failed,
            'failures'   => $failures,
            'assertions' => $this->assertions,
        ];
    }

    protected function assertTrue($cond, string $message = 'esperado verdadeiro'): void
    {
        $this->assertions++;
        if ($cond !== true) {
            $this->failures[] = $message;
        }
    }

    protected function assertFalse($cond, string $message = 'esperado falso'): void
    {
        $this->assertions++;
        if ($cond !== false) {
            $this->failures[] = $message;
        }
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->failures[] = ($message ?: 'valores diferentes')
                . ' (esperado: ' . var_export($expected, true)
                . ', obtido: ' . var_export($actual, true) . ')';
        }
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) {
            $this->failures[] = ($message ?: 'string não contém trecho esperado')
                . ' (procurava: ' . $needle . ')';
        }
    }

    protected function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (strpos($haystack, $needle) !== false) {
            $this->failures[] = ($message ?: 'string contém trecho proibido')
                . ' (não deveria conter: ' . $needle . ')';
        }
    }
}
