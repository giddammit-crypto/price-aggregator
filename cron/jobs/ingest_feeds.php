<?php
declare(strict_types=1);

/**
 * Cron Job: ingest_feeds
 * Ingests partner feeds from local / cached feeds and updates shop shards.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', dirname(__DIR__, 2) . '/app');
require_once dirname(__DIR__, 2) . '/app/helpers.php';

$root = dirname(__DIR__, 2);
\App\Core\Config::init($root . '/config');
\App\Core\Logger::init($root . '/data/logs');

$importer = new \App\Ingest\FeedImporter();
$shops = \App\Core\Config::get('shops', []);

$sampleFeed = $root . '/tests/fixtures/sample_feed.yml';
$processed = 0;

foreach ($shops as $shopId => $meta) {
    if (($meta['mode'] ?? '') !== 'prices') {
        continue;
    }

    $feedFile = $root . "/data/feeds/{$shopId}.xml";
    if (!file_exists($feedFile) && file_exists($sampleFeed)) {
        // Use test fixture for dry-run/demonstration if no production feed is downloaded yet
        $feedFile = $sampleFeed;
    }

    if (file_exists($feedFile)) {
        $res = $importer->import($shopId, $feedFile, 1000);
        echo sprintf(
            "[%s] Ingested shop '%s': total=%d, accepted=%d, rejected=%d, quarantined=%d in %.2fs\n",
            date('Y-m-d H:i:s'),
            $shopId,
            $res['total'],
            $res['accepted'],
            $res['rejected'],
            $res['quarantined'],
            $res['duration_sec']
        );
        $processed++;
        // Limit one sample run to prevent unnecessary work in CLI run
        if ($feedFile === $sampleFeed) {
            break;
        }
    }
}

echo "[{$processed}] shops processed.\n";
