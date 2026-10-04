<?php
declare(strict_types=1);

/**
 * Feed Validation and Catalog Ingestion Tests
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Ingest\FeedValidator;
use App\Ingest\FeedImporter;
use App\Ingest\YmlReader;
use App\Services\DonorUrlHelper;
use App\Storage\Repositories\FileOfferRepository;

echo "=== Running Store Feeds and Ingestion Tests ===\n";

$shops = require $root . '/config/shops.php';
$expectedShops = [
    'citilink', 'regard', 'onlinetrade', 'mvideo', 'dns',
    'ozon', 'wildberries', 'yandex_market', 'megamarket', 'aliexpress', 'joom'
];

$validator = new FeedValidator();
$reader = new YmlReader();
$repo = new FileOfferRepository();

// Test 1: Check all 11 store XML feeds exist
echo "[Test 1] Feed generation & file presence for all 11 shops:\n";
foreach ($expectedShops as $sId) {
    $feedFile = "{$root}/data/feeds/{$sId}.xml";
    if (!file_exists($feedFile)) {
        throw new RuntimeException("Feed file missing for '{$sId}': {$feedFile}");
    }
    echo "  [OK] Found feed: data/feeds/{$sId}.xml\n";
}

// Test 2: Validate feed structure, currencies, categories, and required tags
echo "[Test 2] FeedValidator structural & offer tag audit:\n";
$requiredTags = ['name', 'price', 'oldprice', 'currencyId', 'vendor', 'model', 'url', 'picture'];

foreach ($expectedShops as $sId) {
    $feedFile = "{$root}/data/feeds/{$sId}.xml";
    $report = $validator->validateFeedFile($feedFile, true);

    if (!$report['valid']) {
        throw new RuntimeException("Validation failed for '{$sId}': " . implode('; ', $report['errors']));
    }

    if ($report['total_offers'] !== 40 || $report['valid_offers'] !== 40 || $report['invalid_offers'] !== 0) {
        throw new RuntimeException("Unexpected offer counts for '{$sId}': total={$report['total_offers']}, valid={$report['valid_offers']}, invalid={$report['invalid_offers']}");
    }

    // Check currencies declared
    if (!in_array('RUR', $report['currencies'], true)) {
        throw new RuntimeException("Currency 'RUR' not declared in feed '{$sId}'");
    }

    // Check all required tags are present 100% of the time
    foreach ($requiredTags as $t) {
        $count = $report['tag_stats'][$t] ?? 0;
        if ($count !== 40) {
            throw new RuntimeException("Required tag <{$t}> count ({$count}) in '{$sId}' is less than expected 40");
        }
    }

    echo sprintf("  [OK] %-14s: 40/40 offers verified, all required tags present, currency RUR\n", $sId);
}

// Test 3: Validate DonorUrlHelper integration in feed URLs
echo "[Test 3] URL generation via DonorUrlHelper::buildStoreUrl & cleanModelQuery:\n";
$sampleOffers = iterator_to_array($reader->readOffers("{$root}/data/feeds/yandex_market.xml"));
$first = $sampleOffers[0];

if (empty($first['url']) || !str_contains($first['url'], 'market.yandex.ru')) {
    throw new RuntimeException("Unexpected URL in offer: {$first['url']}");
}
if (!str_contains($first['url'], 'Ryzen%207%207800X3D')) {
    throw new RuntimeException("URL does not contain cleaned model query: {$first['url']}");
}
if ($first['currencyId'] !== 'RUR') {
    throw new RuntimeException("Expected currencyId RUR, got: {$first['currencyId']}");
}
if ($first['model'] !== 'Ryzen 7 7800X3D') {
    throw new RuntimeException("Expected model 'Ryzen 7 7800X3D', got: {$first['model']}");
}
echo "  [OK] First offer URL verified: {$first['url']}\n";
echo "  [OK] Model tag verified: {$first['model']}\n";
echo "  [OK] CurrencyId verified: {$first['currencyId']}\n";

// Test 4: Repository persistence and retrieval
echo "[Test 4] FileOfferRepository shard persistence:\n";
$firstExtId = $first['id'];
$shardName = FileOfferRepository::getShardName($firstExtId);
$shardItems = $repo->getShopShardItems('yandex_market', $shardName);

if (empty($shardItems)) {
    throw new RuntimeException("Failed to retrieve offers from shard {$shardName} for yandex_market");
}

$retrieved = $repo->getOfferByKey('yandex_market', "yandex_market:{$firstExtId}");
if (!$retrieved) {
    throw new RuntimeException("Failed to get offer by key: yandex_market:{$firstExtId}");
}
if (($retrieved['vendor'] ?? '') !== 'AMD' || ($retrieved['model'] ?? '') !== 'Ryzen 7 7800X3D') {
    throw new RuntimeException("Retrieved offer data mismatch");
}
echo "  [OK] Retrieved offer from repository shard: {$retrieved['key']} ({$retrieved['title']})\n";

// Test 5: Malformed feed rejection in FeedValidator
echo "[Test 5] Malformed feed negative validation:\n";
$badOffer = [
    'id' => 'bad-1',
    'name' => '', // Empty name
    'price' => 0,  // Zero price
    'currencyId' => '',
];
$badCheck = $validator->validateOffer($badOffer, ['RUR'], true);
if ($badCheck['valid']) {
    throw new RuntimeException("Expected bad offer to fail validation");
}
if (count($badCheck['errors']) < 3) {
    throw new RuntimeException("Expected multiple errors for bad offer");
}
echo "  [OK] Negative validation passed: caught " . count($badCheck['errors']) . " errors\n";

echo "=== All Store Feeds & Ingestion Tests PASSED Successfully! ===\n";
exit(0);
