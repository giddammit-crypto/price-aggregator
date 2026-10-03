<?php
declare(strict_types=1);

/**
 * File Storage Integrity Checker (FSCK)
 * Verifies packs, index offsets, snapshots, and permissions.
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

\App\Storage\Snapshot::init($root . '/data/snapshots');
\App\Storage\Pack::init($root . '/data/catalog/products');

echo "=== PriceHub File Storage Integrity Check (FSCK) ===\n";

$errors = [];
$warnings = [];

// 1. Check active snapshot
$activeId = \App\Storage\Snapshot::getActiveId();
if (!$activeId) {
    $errors[] = "No active snapshot found in data/snapshots/CURRENT";
} else {
    echo "[OK] Active snapshot: {$activeId}\n";
    $snapDir = $root . "/data/snapshots/{$activeId}/";
    $requiredFiles = ['meta.php', 'home.php', 'categories.php', 'brands.php', 'search/vocab.php'];
    foreach ($requiredFiles as $rf) {
        if (!file_exists($snapDir . $rf)) {
            $errors[] = "Missing required snapshot file: {$rf}";
        }
    }
}

// 2. Check Shard Packs & Indexes
$packsDir = $root . '/data/catalog/products';
$shardsChecked = 0;
$recordsChecked = 0;

for ($i = 0; $i < 256; $i++) {
    $shardName = sprintf('p%03d', $i);
    $dataFile = "{$packsDir}/{$shardName}.ndjson";
    $idxFile = "{$packsDir}/{$shardName}.idx.php";

    if (!file_exists($dataFile) && !file_exists($idxFile)) {
        continue;
    }

    if (!file_exists($dataFile) || !file_exists($idxFile)) {
        $errors[] = "Incomplete shard {$shardName}: data or index missing";
        continue;
    }

    $idx = require $idxFile;
    if (!is_array($idx)) {
        $errors[] = "Shard {$shardName} index is not a valid PHP array";
        continue;
    }

    $shardsChecked++;
    $fp = fopen($dataFile, 'rb');
    if (!$fp) {
        $errors[] = "Cannot open shard data file: {$dataFile}";
        continue;
    }

    // Spot-check up to 5 random records per shard
    $keys = array_keys($idx);
    $samples = count($keys) > 5 ? (array)array_rand(array_flip($keys), 5) : $keys;

    foreach ($samples as $id) {
        $recordsChecked++;
        [$offset, $len] = $idx[$id];
        fseek($fp, $offset);
        $raw = fread($fp, $len);
        $json = @json_decode((string)$raw, true);
        if (!$json || ($json['id'] ?? null) !== $id) {
            $errors[] = "Corrupt record {$id} in shard {$shardName} at offset {$offset}";
            break;
        }
    }
    fclose($fp);
}

echo "[OK] Verified {$shardsChecked} product shards, sampled {$recordsChecked} records.\n";

// 3. Check Directory Permissions
$writableDirs = [
    $root . '/data/catalog/products',
    $root . '/data/snapshots',
    $root . '/data/state',
    $root . '/data/state/locks',
    $root . '/data/logs',
];

foreach ($writableDirs as $dir) {
    if (!is_dir($dir) || !is_writable($dir)) {
        $warnings[] = "Directory {$dir} is not writable or missing";
    }
}

echo "---------------------------------------------------\n";
if (!empty($errors)) {
    echo "FAILED with " . count($errors) . " errors:\n";
    foreach ($errors as $e) {
        echo "  [ERROR] {$e}\n";
    }
    exit(1);
}

if (!empty($warnings)) {
    echo "PASSED with " . count($warnings) . " warnings:\n";
    foreach ($warnings as $w) {
        echo "  [WARN] {$w}\n";
    }
} else {
    echo "PASSED: File storage integrity score: 100%. All shards, indexes and snapshots OK.\n";
}
exit(0);
