<?php
/**
 * Atomic File System Operations and Thread-Safe Locking
 */

declare(strict_types=1);

namespace App\Storage;

class Fs
{
    /**
     * Atomically write file using temporary file and rename
     */
    public static function atomicWrite(string $filePath, string $content): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $tmpFile = $dir . '/.tmp_' . basename($filePath) . '_' . bin2hex(random_bytes(6));
        $bytes = @file_put_contents($tmpFile, $content, LOCK_EX);
        if ($bytes === false) {
            return false;
        }

        // Ensure written to disk
        $fh = @fopen($tmpFile, 'r');
        if ($fh) {
            if (function_exists('fsync')) {
                @fsync($fh);
            }
            fclose($fh);
        }

        $res = @rename($tmpFile, $filePath);
        if (!$res) {
            @unlink($tmpFile);
        }
        return $res;
    }

    /**
     * Atomically write array as executable PHP file for OPcache
     */
    public static function atomicWritePhpArray(string $filePath, array $data): bool
    {
        $exported = var_export($data, true);
        $code = "<?php\ndeclare(strict_types=1);\nreturn " . $exported . ";\n";
        return self::atomicWrite($filePath, $code);
    }

    /**
     * Thread-safe append single line with flock (e.g. clicks, history, subs)
     */
    public static function appendLine(string $filePath, string $line): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fh = @fopen($filePath, 'ab');
        if (!$fh) {
            return false;
        }

        flock($fh, LOCK_EX);
        $written = fwrite($fh, rtrim($line) . "\n");
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);

        return $written !== false;
    }

    /**
     * Read and decode JSON file safely
     */
    public static function readJson(string $filePath, mixed $default = null): mixed
    {
        if (!file_exists($filePath)) {
            return $default;
        }
        $content = @file_get_contents($filePath);
        if ($content === false || $content === '') {
            return $default;
        }
        $data = json_decode($content, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : $default;
    }

    /**
     * Write JSON atomically
     */
    public static function writeJson(string $filePath, mixed $data, int $flags = JSON_UNESCAPED_UNICODE): bool
    {
        $json = json_encode($data, $flags);
        if ($json === false) {
            return false;
        }
        return self::atomicWrite($filePath, $json);
    }

    /**
     * Execute callback within exclusive lock
     */
    public static function withLock(string $lockFile, callable $callback, bool $blocking = false): mixed
    {
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fh = @fopen($lockFile, 'c+');
        if (!$fh) {
            return null;
        }

        $flags = $blocking ? LOCK_EX : (LOCK_EX | LOCK_NB);
        if (!flock($fh, $flags)) {
            fclose($fh);
            return null; // Could not acquire lock
        }

        try {
            return $callback();
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
