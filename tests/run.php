<?php
declare(strict_types=1);

/**
 * PriceHub Complete Test Runner
 * Executes unit, integration, storage concurrency, fsck, and smoke tests.
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

\App\Core\Config::init($root . '/config');
\App\Core\Logger::init($root . '/data/logs');

echo "========================================================\n";
echo "           PRICEHUB TEST SUITE RUNNER                   \n";
echo "========================================================\n\n";

$failed = 0;
$passed = 0;

function assertTest(string $name, bool $condition, string $msg = ''): void {
    global $failed, $passed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name}: {$msg}\n";
    }
}

// 1. Storage Concurrency & Rollback Test
echo "[TEST SUITE 1] Storage Concurrency & Rollback\n";
passthru('php ' . escapeshellarg($root . '/tests/storage_test.php'), $ret);
if ($ret === 0) {
    $passed++;
} else {
    $failed++;
}
echo "\n";

// 2. Unit Tests: Normalizer & TrustFilter
echo "[TEST SUITE 2] Normalizer & TrustFilter\n";
$filter = new \App\Ingest\TrustFilter();

// Test global spam
$resSpam = $filter->evaluate(['name' => 'Копия iPhone 15 Pro Max', 'price' => 10000]);
assertTest('Global spam rejection', $resSpam['pass'] === false && $resSpam['action'] === 'reject', 'Expected rejection for replica');

// Test category accessory filter
$resAcc = $filter->evaluate(['name' => 'Чехол для iPhone 15 силикон', 'price' => 500], 30);
assertTest('Category accessory rejection', $resAcc['pass'] === false && $resAcc['action'] === 'reject', 'Expected rejection for accessory in smartphones');

// Test price anomaly quarantine
$resAnomaly = $filter->evaluate(['name' => 'Видеокарта RTX 4070', 'price' => 5000], 14, 65000);
assertTest('Price anomaly quarantine', $resAnomaly['pass'] === false && $resAnomaly['action'] === 'quarantine', 'Expected quarantine for 5k RTX 4070');

// Test valid offer
$resValid = $filter->evaluate(['name' => 'Смартфон Xiaomi 14 12/256GB', 'price' => 64990], 30, 68000);
assertTest('Valid offer acceptance', $resValid['pass'] === true, 'Expected acceptance for genuine offer');
echo "\n";

// 3. Unit Tests: MatchingService
echo "[TEST SUITE 3] MatchingService Cascades\n";
$matcher = new \App\Services\MatchingService();
$candidates = [
    [
        'id' => 101,
        'title' => 'Видеокарта Gigabyte GeForce RTX 4070 GAMING OC 12G',
        'barcode' => '4719331313418',
        'mpn' => 'GV-N4070GAMING OC-12GD'
    ],
    [
        'id' => 102,
        'title' => 'Смартфон Apple iPhone 15 128GB Black',
        'barcode' => '195949038234',
        'mpn' => 'MTP03ZD/A'
    ]
];

// Test barcode exact match
$mEan = $matcher->matchOffer(['ean' => '4719331313418', 'title' => 'RTX 4070 Gigabyte'], $candidates);
assertTest('Barcode EAN exact match', $mEan['matched'] && $mEan['product_id'] === 101 && $mEan['method'] === 'ean_exact');

// Test MPN exact match
$mMpn = $matcher->matchOffer(['mpn' => 'MTP03ZD/A', 'title' => 'iPhone 15'], $candidates);
assertTest('MPN exact match', $mMpn['matched'] && $mMpn['product_id'] === 102 && $mMpn['method'] === 'mpn_exact');

// Test Token similarity match
$mSim = $matcher->matchOffer(['title' => 'Apple iPhone 15 128GB Black смартфон'], $candidates);
assertTest('Token similarity high match', $mSim['matched'] && $mSim['product_id'] === 102);
echo "\n";

// 4. File Storage Integrity (FSCK)
echo "[TEST SUITE 4] FSCK Shard & Offset Integrity\n";
passthru('php ' . escapeshellarg($root . '/bin/fsck.php'), $retFsck);
if ($retFsck === 0) {
    $passed++;
} else {
    $failed++;
}
echo "\n";

// 5. Smoke Tests across HTTP Routes
echo "[TEST SUITE 5] HTTP Routes Smoke Tests\n";
passthru('php ' . escapeshellarg($root . '/bin/smoke.php'), $retSmoke);
if ($retSmoke === 0) {
    $passed++;
} else {
    $failed++;
}
echo "\n";

echo "========================================================\n";
if ($failed === 0) {
    echo "  ALL TESTS PASSED! ({$passed} checks passed, 0 failures) \n";
    echo "========================================================\n";
    exit(0);
} else {
    echo "  TEST RUN FAILED! ({$passed} passed, {$failed} failures) \n";
    echo "========================================================\n";
    exit(1);
}
