<?php
/**
 * Packed NDJSON Products storage with OPcache-accelerated index
 */

declare(strict_types=1);

namespace App\Storage;

class Pack
{
    private static string $baseDir = '';
    private static array $indexCache = [];

    public static function init(string $baseDir): void
    {
        self::$baseDir = rtrim($baseDir, '/') . '/';
        if (!is_dir(self::$baseDir)) {
            @mkdir(self::$baseDir, 0775, true);
        }
    }

    public static function getShardName(int $productId): string
    {
        return sprintf('p%03d', $productId % 256);
    }

    /**
     * Read product by ID in O(1) using fseek and index offset
     */
    public static function get(int $productId): ?array
    {
        $shard = self::getShardName($productId);
        $idx = self::loadIndex($shard);

        if (!isset($idx[$productId])) {
            return null;
        }

        [$offset, $len] = $idx[$productId];
        $ndjsonFile = self::$baseDir . $shard . '.ndjson';
        if (!file_exists($ndjsonFile)) {
            return null;
        }

        $fh = @fopen($ndjsonFile, 'rb');
        if (!$fh) {
            return null;
        }

        fseek($fh, $offset, SEEK_SET);
        $line = fread($fh, $len);
        fclose($fh);

        if ($line === false || $line === '') {
            return null;
        }

        $data = json_decode($line, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Read multiple products by IDs
     */
    public static function getMultiple(array $productIds): array
    {
        $results = [];
        $byShard = [];
        foreach ($productIds as $id) {
            $id = (int)$id;
            $byShard[self::getShardName($id)][] = $id;
        }

        foreach ($byShard as $shard => $ids) {
            $idx = self::loadIndex($shard);
            $ndjsonFile = self::$baseDir . $shard . '.ndjson';
            if (!file_exists($ndjsonFile)) {
                continue;
            }

            $fh = @fopen($ndjsonFile, 'rb');
            if (!$fh) {
                continue;
            }

            foreach ($ids as $id) {
                if (isset($idx[$id])) {
                    [$offset, $len] = $idx[$id];
                    fseek($fh, $offset, SEEK_SET);
                    $line = fread($fh, $len);
                    if ($line !== false) {
                        $data = json_decode($line, true);
                        if (is_array($data)) {
                            $results[$id] = $data;
                        }
                    }
                }
            }
            fclose($fh);
        }

        return $results;
    }

    /**
     * Load index array (cached by OPcache)
     */
    public static function loadIndex(string $shard): array
    {
        if (isset(self::$indexCache[$shard])) {
            return self::$indexCache[$shard];
        }

        $idxFile = self::$baseDir . $shard . '.idx.php';
        if (file_exists($idxFile)) {
            $data = require $idxFile;
            if (is_array($data)) {
                self::$indexCache[$shard] = $data;
                return $data;
            }
        }

        return [];
    }

    /**
     * Atomically write entire shard of products and its offset index
     */
    public static function writePack(string $shard, array $products): bool
    {
        $ndjsonFile = self::$baseDir . $shard . '.ndjson';
        $idxFile = self::$baseDir . $shard . '.idx.php';

        $buffer = '';
        $index = [];
        $offset = 0;

        foreach ($products as $p) {
            $id = (int)$p['id'];
            $line = json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            $len = strlen($line);
            $index[$id] = [$offset, $len];
            $buffer .= $line;
            $offset += $len;
        }

        $writeNdjson = Fs::atomicWrite($ndjsonFile, $buffer);
        $writeIdx = Fs::atomicWritePhpArray($idxFile, $index);

        self::$indexCache[$shard] = $index;

        return $writeNdjson && $writeIdx;
    }

    /**
     * Read all products from a shard
     */
    public static function getAllFromShard(string $shard): array
    {
        $ndjsonFile = self::$baseDir . $shard . '.ndjson';
        if (!file_exists($ndjsonFile)) {
            return [];
        }

        $results = [];
        $fh = @fopen($ndjsonFile, 'rb');
        if ($fh) {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line !== '') {
                    $item = json_decode($line, true);
                    if (is_array($item) && isset($item['id'])) {
                        $results[$item['id']] = $item;
                    }
                }
            }
            fclose($fh);
        }
        return $results;
    }
}
