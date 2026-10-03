<?php
declare(strict_types=1);

namespace App\Core;

class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        Logger::error("PHP Notice/Warning: {$message} in {$file}:{$line}");
        return true;
    }

    public static function handleException(\Throwable $e): void
    {
        Logger::error("Uncaught exception: " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (PHP_SAPI === 'cli') {
            echo "\n[ERROR] " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (self::$debug) {
            echo "<h1>Application Error</h1>";
            echo "<p><strong>" . e($e->getMessage()) . "</strong></p>";
            echo "<p>" . e($e->getFile()) . ":" . $e->getLine() . "</p>";
            echo "<pre>" . e($e->getTraceAsString()) . "</pre>";
        } else {
            $view = new View();
            $view->setLayout('layout/main');
            echo $view->render('errors/500', [
                'pageTitle' => 'Ошибка 500 — Сбой сервера'
            ]);
        }
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            Logger::error("Fatal shutdown error: {$error['message']} in {$error['file']}:{$error['line']}");
            if (!headers_sent() && PHP_SAPI !== 'cli') {
                http_response_code(500);
                echo "<h1>500 Internal Server Error</h1>";
            }
        }
    }
}
