<?php
declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    private static string $storageDir = '';

    public static function init(string $storageDir): void
    {
        self::$storageDir = rtrim($storageDir, '/') . '/';
        if (!is_dir(self::$storageDir)) {
            @mkdir(self::$storageDir, 0775, true);
        }
    }

    /**
     * Check rate limit: returns true if request is allowed, false if limit exceeded.
     */
    public static function check(string $key, int $maxHits = 60, int $windowSeconds = 60): bool
    {
        if (empty(self::$storageDir)) {
            return true;
        }

        $hash = hash('xxh128', $key);
        $file = self::$storageDir . $hash . '.json';
        $now = time();

        $data = ['hits' => 0, 'reset' => $now + $windowSeconds];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['reset']) && $decoded['reset'] > $now) {
                    $data = $decoded;
                }
            }
        }

        if ($data['hits'] >= $maxHits) {
            return false;
        }

        $data['hits']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }
}
