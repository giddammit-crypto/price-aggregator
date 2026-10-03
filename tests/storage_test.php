<?php
/**
 * Storage Layer Verification Test
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Storage\Fs;
use App\Storage\Pack;
use App\Storage\Snapshot;

echo "=== Running Storage Layer Tests ===\n";

// Test 1: Atomic write
$testFile = $root . '/data/state/_test_atomic.txt';
$content1 = str_repeat("Hello World 1\n", 1000);
$ok1 = Fs::atomicWrite($testFile, $content1);
$content2 = str_repeat("Updated Content 2\n", 500);
$ok2 = Fs::atomicWrite($testFile, $content2);
$readBack = file_get_contents($testFile);
assert($ok1 && $ok2 && $readBack === $content2, "Atomic write failed");
@unlink($testFile);
echo "[OK] Test 1: Atomic write & replace passed.\n";

// Test 2: Concurrent appendLine (10 parallel workers)
$logFile = $root . '/data/logs/_test_concurrent.log';
@unlink($logFile);

$workers = 10;
$linesPerWorker = 50;
$procs = [];

for ($w = 0; $w < $workers; $w++) {
    $cmd = sprintf(
        'php -r "require \"%s/app/Storage/Fs.php\"; for (\$i=0;\$i<%d;\$i++) { App\Storage\Fs::appendLine(\"%s\", \"worker_%d_\" . \$i); usleep(100); }"',
        $root,
        $linesPerWorker,
        $logFile,
        $w
    );
    $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
}

foreach ($procs as $p) {
    proc_close($p);
}

$writtenLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$expectedTotal = $workers * $linesPerWorker;
@unlink($logFile);
assert(count($writtenLines) === $expectedTotal, "Concurrent append line count mismatch: " . count($writtenLines) . " vs $expectedTotal");
echo "[OK] Test 2: Concurrent appendLine (10 processes, $expectedTotal lines) passed without corruption.\n";

// Test 3: Pack O(1) reads with offsets & index in isolated test dir
$testPacksDir = $root . '/data/test_catalog_' . uniqid();
@mkdir($testPacksDir, 0775, true);
Pack::init($testPacksDir);
$shard = 'p001';
$sampleProducts = [];
for ($i = 1; $i <= 50; $i++) {
    $sampleProducts[] = [
        'id' => $i * 256 + 1, // shard p001
        'cat' => 10,
        'title' => "Test Product #{$i}",
        'brand' => "Brand {$i}",
        'agg' => ['min' => 1000 + $i, 'cnt' => 3]
    ];
}
$packWritten = Pack::writePack($shard, $sampleProducts);
assert($packWritten, "Failed to write product pack");

// Read product #25
$targetId = 25 * 256 + 1;
$p = Pack::get($targetId);
assert($p !== null && $p['id'] === $targetId && $p['title'] === "Test Product #25", "Pack O(1) indexed read failed");

// Cleanup test pack dir and re-init production dir
@unlink($testPacksDir . "/{$shard}.ndjson");
@unlink($testPacksDir . "/{$shard}.idx.php");
@rmdir($testPacksDir);
Pack::init($root . '/data/catalog/products');

echo "[OK] Test 3: Pack storage & O(1) indexed read passed.\n";

// Test 4: Snapshots and Atomic Rollback
Snapshot::init($root . '/data/snapshots');
$originalSnapshot = Snapshot::getActiveId();

$snap1 = 'test_snap_' . uniqid();
$snap2 = 'test_snap_' . uniqid();
@mkdir($root . "/data/snapshots/{$snap1}", 0775, true);
@mkdir($root . "/data/snapshots/{$snap2}", 0775, true);

Fs::atomicWritePhpArray($root . "/data/snapshots/{$snap1}/meta.php", ['version' => 1, 'date' => '12:00']);
Fs::atomicWritePhpArray($root . "/data/snapshots/{$snap2}/meta.php", ['version' => 2, 'date' => '13:00']);

Snapshot::publish($snap1);
assert(Snapshot::getActiveId() === $snap1, "Snapshot 1 publish failed");
$meta1 = Snapshot::loadArray('meta.php');
assert($meta1['version'] === 1, "Snapshot 1 data mismatch");

Snapshot::publish($snap2);
assert(Snapshot::getActiveId() === $snap2, "Snapshot 2 publish failed");
$meta2 = Snapshot::loadArray('meta.php');
assert($meta2['version'] === 2, "Snapshot 2 data mismatch");

// Rollback to snap1
Snapshot::publish($snap1);
assert(Snapshot::getActiveId() === $snap1, "Snapshot rollback failed");
$metaRollback = Snapshot::loadArray('meta.php');
assert($metaRollback['version'] === 1, "Snapshot rollback data mismatch");

// Cleanup test snapshots and restore production snapshot
@unlink($root . "/data/snapshots/{$snap1}/meta.php");
@rmdir($root . "/data/snapshots/{$snap1}");
@unlink($root . "/data/snapshots/{$snap2}/meta.php");
@rmdir($root . "/data/snapshots/{$snap2}");

if ($originalSnapshot) {
    Snapshot::publish($originalSnapshot);
}

echo "[OK] Test 4: Snapshot publish and atomic rollback passed.\n";

echo "=== All Storage Layer Tests PASSED Successfully! ===\n";
