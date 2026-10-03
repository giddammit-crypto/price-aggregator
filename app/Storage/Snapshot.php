<?php
/**
 * Zero-Downtime Read-Only Snapshot Manager
 */

declare(strict_types=1);

namespace App\Storage;

class Snapshot
{
    private static string $baseDir = '';
    private static ?string $currentId = null;
    private static array $fileCache = [];

    public static function init(string $baseDir): void
    {
        self::$baseDir = rtrim($baseDir, '/') . '/';
        self::$currentId = null;
        self::$fileCache = [];
        if (!is_dir(self::$baseDir)) {
            @mkdir(self::$baseDir, 0775, true);
        }
    }

    public static function getActiveId(): ?string
    {
        if (self::$baseDir === '') {
            self::init(dirname(__DIR__, 2) . '/data/snapshots');
        }
        if (self::$currentId !== null) {
            return self::$currentId;
        }

        $currentFile = self::$baseDir . 'CURRENT';
        if (file_exists($currentFile)) {
            $id = trim((string)@file_get_contents($currentFile));
            if ($id !== '' && is_dir(self::$baseDir . $id)) {
                self::$currentId = $id;
                return $id;
            }
        }
        return null;
    }

    public static function getActivePath(): ?string
    {
        $id = self::getActiveId();
        return $id ? (self::$baseDir . $id . '/') : null;
    }

    /**
     * Load array file from active snapshot (cached in memory and OPcache)
     */
    public static function loadArray(string $relativeFile, mixed $default = []): mixed
    {
        $path = self::getActivePath();
        if (!$path) {
            return $default;
        }

        $cacheKey = self::$currentId . ':' . $relativeFile;
        if (isset(self::$fileCache[$cacheKey])) {
            return self::$fileCache[$cacheKey];
        }

        $fullPath = $path . ltrim($relativeFile, '/');
        if (file_exists($fullPath)) {
            $data = require $fullPath;
            if (is_array($data)) {
                self::$fileCache[$cacheKey] = $data;
                return $data;
            }
        }

        return $default;
    }

    /**
     * Publish newly compiled snapshot atomically
     */
    public static function publish(string $newSnapshotId): bool
    {
        $targetDir = self::$baseDir . $newSnapshotId;
        if (!is_dir($targetDir)) {
            return false;
        }

        $currentFile = self::$baseDir . 'CURRENT';
        $res = Fs::atomicWrite($currentFile, $newSnapshotId);
        if ($res) {
            self::$currentId = $newSnapshotId;
            self::$fileCache = [];
        }
        return $res;
    }

    /**
     * Clean up snapshots older than latest $keep
     */
    public static function cleanup(int $keep = 2): int
    {
        $dirs = glob(self::$baseDir . '20*', GLOB_ONLYDIR);
        if (!$dirs || count($dirs) <= $keep) {
            return 0;
        }

        sort($dirs);
        $toRemove = array_slice($dirs, 0, count($dirs) - $keep);
        $activeId = self::getActiveId();
        $count = 0;

        foreach ($toRemove as $dir) {
            if ($activeId && basename($dir) === $activeId) {
                continue;
            }
            self::recursiveRmdir($dir);
            $count++;
        }
        return $count;
    }

    public static function cleanupOld(int $keep = 2): void
    {
        self::cleanup($keep);
    }

    private static function recursiveRmdir(string $dir): void
    {
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $full = $dir . '/' . $file;
            is_dir($full) ? self::recursiveRmdir($full) : @unlink($full);
        }
        @rmdir($dir);
    }
}
