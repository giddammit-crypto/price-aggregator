<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $items = [];
    private static string $configPath = '';

    public static function init(string $configPath): void
    {
        self::$configPath = rtrim($configPath, '/') . '/';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$items[$file])) {
            $filePath = self::$configPath . $file . '.php';
            if (file_exists($filePath)) {
                self::$items[$file] = require $filePath;
            } else {
                return $default;
            }
        }

        $data = self::$items[$file];
        foreach ($parts as $part) {
            if (is_array($data) && array_key_exists($part, $data)) {
                $data = $data[$part];
            } else {
                return $default;
            }
        }

        return $data;
    }

    public static function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        if (!isset(self::$items[$file])) {
            self::$items[$file] = [];
        }
        $ref = &self::$items[$file];
        foreach ($parts as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref = &$ref[$part];
        }
        $ref = $value;
    }
}
