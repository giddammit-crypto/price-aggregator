<?php
declare(strict_types=1);

namespace App\Core;

class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        if (self::$loaded || !file_exists($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                self::$vars[$k] = $v;
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        return $default;
    }
}
