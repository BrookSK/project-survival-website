<?php

namespace App\Core;

/**
 * Logger simples baseado em arquivo.
 *
 * Grava logs em storage/logs/ com rotação diária por nível.
 * Nunca expõe stack traces ao visitante; apenas registra no arquivo.
 */
class Logger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    public static function log(string $level, string $message, array $context = []): void
    {
        $path = Config::get('app.logs.path', LOGS_PATH);

        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        $date = date('Y-m-d');
        $file = $path . DIRECTORY_SEPARATOR . "app-{$date}.log";

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Registra uma exceção de forma completa (para o arquivo apenas).
     */
    public static function exception(\Throwable $e): void
    {
        self::error($e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);
    }
}
