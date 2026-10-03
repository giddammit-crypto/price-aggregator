<?php
declare(strict_types=1);

/**
 * Builds clean production release archive (dist.zip) for shared hosting deployment.
 */

$root = dirname(__DIR__);
$distZip = $root . '/dist.zip';

echo "Building production release: {$distZip}...\n";

// Remove old dist
if (file_exists($distZip)) {
    @unlink($distZip);
}

// Ensure assets are minified
passthru('php ' . escapeshellarg($root . '/bin/build-assets.php'));

$cmd = sprintf(
    "zip -r -q %s . -x '.git*' 'backups/*' 'data/logs/*' 'data/state/locks/*' 'dist.zip' 'node_modules/*'",
    escapeshellarg($distZip)
);

exec($cmd, $output, $code);

if ($code === 0 && file_exists($distZip)) {
    $sizeMb = round(filesize($distZip) / 1048576, 2);
    echo "[OK] Production archive dist.zip built successfully ({$sizeMb} MB).\n";
} else {
    echo "[ERROR] Failed to create dist.zip. Code: {$code}\n";
    exit(1);
}
