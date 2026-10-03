<?php
declare(strict_types=1);

namespace App\Core;

class Logger
{
    private static string $logDir = '';

    public static function init(string $logDir): void
    {
        self::$logDir = rtrim($logDir, '/') . '/';
    }

    public static function log(string $channel, string $level, string $message, array $context = []): void
    {
        $dir = self::$logDir . $channel;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $date = date('Y-m-d');
        $file = $dir . '/' . $date . '.log';
        $time = date('Y-m-d H:i:s');
        $ctx = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$time}] [{$level}] {$message}{$ctx}\n";

        $fh = @fopen($file, 'ab');
        if ($fh) {
            flock($fh, LOCK_EX);
            fwrite($fh, $line);
            fflush($fh);
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('app', 'INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('errors', 'ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('app', 'WARNING', $message, $context);
    }
}
