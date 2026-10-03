<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Logger;
use App\Storage\Fs;
use App\Storage\Repositories\FileOfferRepository;

class FeedImporter
{
    private string $itemsDir;
    private string $cursorsDir;
    private string $quarantineFile;
    private YmlReader $reader;
    private OfferNormalizer $normalizer;
    private TrustFilter $filter;
    private HttpClient $http;

    public function __construct(
        string $itemsDir = '',
        string $cursorsDir = '',
        ?YmlReader $reader = null,
        ?OfferNormalizer $normalizer = null,
        ?TrustFilter $filter = null,
        ?HttpClient $http = null
    ) {
        $root = dirname(__DIR__, 2);
        $this->itemsDir = rtrim($itemsDir ?: ($root . '/data/items'), '/') . '/';
        $this->cursorsDir = rtrim($cursorsDir ?: ($root . '/data/state/cursors'), '/') . '/';
        $this->quarantineFile = $root . '/data/state/quarantine.ndjson';

        $this->reader = $reader ?: new YmlReader();
        $this->normalizer = $normalizer ?: new OfferNormalizer();
        $this->filter = $filter ?: new TrustFilter();
        $this->http = $http ?: new HttpClient();

        if (!is_dir($this->cursorsDir)) {
            @mkdir($this->cursorsDir, 0775, true);
        }
    }

    /**
     * Imports a feed for a given shop.
     *
     * @param string $shopId
     * @param string $feedPath Path or URL
     * @param int $maxItems Limit processing if needed for batching
     * @return array{total: int, accepted: int, rejected: int, quarantined: int, duration_sec: float}
     */
    public function import(string $shopId, string $feedPath, int $maxItems = 50000): array
    {
        $start = microtime(true);
        $shopDir = $this->itemsDir . $shopId . '/';
        $tempShopDir = $this->itemsDir . $shopId . '_tmp_' . uniqid() . '/';
        @mkdir($tempShopDir, 0775, true);

        // Open shard file pointers on demand
        /** @var array<string, resource> $shardFPs */
        $shardFPs = [];

        $total = 0;
        $accepted = 0;
        $rejected = 0;
        $quarantined = 0;

        try {
            foreach ($this->reader->readOffers($feedPath) as $rawOffer) {
                $total++;
                if ($total > $maxItems) {
                    break;
                }

                $normalized = $this->normalizer->normalize($rawOffer, $shopId);

                $categoryId = !empty($rawOffer['categoryId']) ? (int)$rawOffer['categoryId'] : null;
                $check = $this->filter->evaluate($normalized, $categoryId);
                if (!$check['pass']) {
                    if ($check['action'] === 'quarantine') {
                        $quarantined++;
                        Fs::appendLine($this->quarantineFile, (string)json_encode([
                            'shop' => $shopId,
                            'date' => date('Y-m-d H:i:s'),
                            'reason' => $check['reason'],
                            'offer' => $normalized
                        ], JSON_UNESCAPED_UNICODE));
                    } else {
                        $rejected++;
                    }
                    continue;
                }

                $accepted++;
                $shardName = FileOfferRepository::getShardName($normalized['external_id']);
                if (!isset($shardFPs[$shardName])) {
                    $shardFile = $tempShopDir . "{$shardName}.ndjson";
                    $shardFPs[$shardName] = fopen($shardFile, 'ab');
                }

                fwrite($shardFPs[$shardName], json_encode($normalized, JSON_UNESCAPED_UNICODE) . "\n");
            }
        } finally {
            // Close all shard handles
            foreach ($shardFPs as $fp) {
                if (is_resource($fp)) {
                    fclose($fp);
                }
            }
        }

        // Atomically replace shop shards
        if (!is_dir($shopDir)) {
            @mkdir($shopDir, 0775, true);
        }

        $tempFiles = glob($tempShopDir . '*.ndjson') ?: [];
        foreach ($tempFiles as $tFile) {
            $base = basename($tFile);
            @rename($tFile, $shopDir . $base);
        }
        @rmdir($tempShopDir);

        $duration = round(microtime(true) - $start, 3);
        $result = [
            'total' => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'quarantined' => $quarantined,
            'duration_sec' => $duration,
        ];

        // Save cursor state
        $cursorFile = $this->cursorsDir . "ingest_{$shopId}.json";
        Fs::atomicWrite($cursorFile, (string)json_encode([
            'shop' => $shopId,
            'last_run' => date('Y-m-d H:i:s'),
            'metrics' => $result
        ], JSON_PRETTY_PRINT));

        Logger::info("FeedImporter [{$shopId}]: read {$total}, accepted {$accepted}, quarantined {$quarantined}, rejected {$rejected} in {$duration}s");
        return $result;
    }
}
