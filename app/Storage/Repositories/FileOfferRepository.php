<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

use App\Storage\Fs;

class FileOfferRepository implements OfferRepositoryInterface
{
    private string $baseDir;

    public function __construct(string $baseDir = '')
    {
        $this->baseDir = rtrim($baseDir ?: (dirname(__DIR__, 3) . '/data/items'), '/') . '/';
    }

    public static function getShardName(string $externalId): string
    {
        return sprintf('s%02x', crc32($externalId) % 256);
    }

    public function getShopShardItems(string $shop, string $shard): array
    {
        $file = $this->baseDir . "{$shop}/{$shard}.ndjson";
        if (!file_exists($file)) {
            return [];
        }

        $items = [];
        $fh = @fopen($file, 'rb');
        if ($fh) {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line !== '') {
                    $row = json_decode($line, true);
                    if (is_array($row) && isset($row['k'])) {
                        $items[$row['k']] = $row;
                    }
                }
            }
            fclose($fh);
        }
        return $items;
    }

    public function saveShopShardItems(string $shop, string $shard, array $items): bool
    {
        $file = $this->baseDir . "{$shop}/{$shard}.ndjson";
        $buffer = '';
        foreach ($items as $item) {
            $buffer .= json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }
        return Fs::atomicWrite($file, $buffer);
    }

    public function getAllShopShards(string $shop): array
    {
        $dir = $this->baseDir . $shop;
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir . '/s*.ndjson') ?: [];
        $shards = [];
        foreach ($files as $file) {
            $shards[] = basename($file, '.ndjson');
        }
        return $shards;
    }
}
