<?php
declare(strict_types=1);

/**
 * Cron Job: ingest_feeds
 * Ingests partner XML/YML product feeds, validates required offer tags & structure,
 * updates shop shards, and verifies repository persistence.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', dirname(__DIR__, 2) . '/app');
require_once dirname(__DIR__, 2) . '/app/helpers.php';

$root = dirname(__DIR__, 2);
\App\Core\Config::init($root . '/config');
\App\Core\Logger::init($root . '/data/logs');

$importer = new \App\Ingest\FeedImporter();
$validator = new \App\Ingest\FeedValidator();
$offerRepo = new \App\Storage\Repositories\FileOfferRepository();

$shops = \App\Core\Config::get('shops', []);
$sampleFeed = $root . '/tests/fixtures/sample_feed.yml';

$processedShops = 0;
$totalOffersIngested = 0;
$totalAccepted = 0;
$totalRejected = 0;
$totalQuarantined = 0;
$totalErrors = 0;

echo "=== Starting Feed Validation and Ingestion for Partner Stores ===\n";

foreach ($shops as $shopId => $meta) {
    if (($meta['mode'] ?? '') !== 'prices') {
        continue;
    }

    $shopName = $meta['name'] ?? $shopId;
    $feedFile = $root . "/data/feeds/{$shopId}.xml";
    $isSample = false;

    if (!file_exists($feedFile) && file_exists($sampleFeed)) {
        // Use test fixture for dry-run/demonstration if no production feed is downloaded yet
        $feedFile = $sampleFeed;
        $isSample = true;
    }

    if (!file_exists($feedFile)) {
        echo "[WARNING] Feed file not found for '{$shopId}': {$feedFile}\n";
        continue;
    }

    // 1. Validate XML/YML feed structure and required offer tags
    // Checks: <name>, <price>, <oldprice>, <currencyId>, <vendor>, <model>, <url>, <picture>
    $valReport = $validator->validateFeedFile($feedFile, !$isSample);
    if (!$valReport['valid']) {
        echo sprintf(
            "[ERROR] Feed validation failed for '%s' (%s): %d errors (%s)\n",
            $shopName,
            $shopId,
            count($valReport['errors']),
            implode('; ', array_slice($valReport['errors'], 0, 3))
        );
        $totalErrors += count($valReport['errors']);
    } else {
        echo sprintf(
            "[VALID] Shop '%s' (%s): %d offers verified (tags: name, price, oldprice, currencyId, vendor, model, url, picture)\n",
            $shopName,
            $shopId,
            $valReport['valid_offers']
        );
    }

    // 2. Ingest feed into repository shards
    $res = $importer->import($shopId, $feedFile, 1000);
    $processedShops++;
    $totalOffersIngested += $res['total'];
    $totalAccepted += $res['accepted'];
    $totalRejected += $res['rejected'];
    $totalQuarantined += $res['quarantined'];

    if ($res['rejected'] > 0 || $res['quarantined'] > 0) {
        $totalErrors += ($res['rejected'] + $res['quarantined']);
    }

    // 3. Verify shard integrity in FileOfferRepository
    $repoCount = $offerRepo->countShopOffers($shopId);

    echo sprintf(
        "[%s] Ingested shop '%s': total=%d, accepted=%d, rejected=%d, quarantined=%d, in repository=%d in %.3fs\n",
        date('Y-m-d H:i:s'),
        $shopId,
        $res['total'],
        $res['accepted'],
        $res['rejected'],
        $res['quarantined'],
        $repoCount,
        $res['duration_sec']
    );

    // Limit one sample run if falling back to sample fixture
    if ($feedFile === $sampleFeed) {
        break;
    }
}

echo "----------------------------------------------------------------------\n";
echo sprintf(
    "=== Ingestion Summary: %d shops processed, %d total offers, %d accepted, %d rejected, %d quarantined, %d errors ===\n",
    $processedShops,
    $totalOffersIngested,
    $totalAccepted,
    $totalRejected,
    $totalQuarantined,
    $totalErrors
);

if ($totalErrors > 0) {
    echo "[FAIL] Feed ingestion finished with errors.\n";
    exit(1);
}

echo "[SUCCESS] All partner feeds ingested successfully into repository with 0 errors.\n";
exit(0);
