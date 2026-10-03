<?php
declare(strict_types=1);

/**
 * Restore utility for PriceHub
 * Restores config and data from tar.gz archive.
 */

$root = dirname(__DIR__);
$backupDir = $root . '/backups';

$archive = $argv[1] ?? null;
if (!$archive) {
    $files = glob("{$backupDir}/backup_*.tar.gz") ?: [];
    if (!$files) {
        echo "[ERROR] No backups found in {$backupDir}\n";
        exit(1);
    }
    sort($files);
    $archive = end($files);
}

if (!file_exists($archive)) {
    echo "[ERROR] Archive not found: {$archive}\n";
    exit(1);
}

echo "Restoring from {$archive} into {$root}...\n";

$cmd = sprintf(
    "tar -xzf %s -C %s",
    escapeshellarg($archive),
    escapeshellarg($root)
);

exec($cmd, $output, $returnCode);

if ($returnCode === 0) {
    echo "[OK] Files restored. Rebuilding catalog snapshot index...\n";
    require_once $root . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    \App\Core\Autoloader::addNamespace('App', $root . '/app');
    require_once $root . '/app/helpers.php';

    $indexer = new \App\Services\BuildIndexService($root);
    $msg = $indexer->run();
    echo "[OK] {$msg}\n";
    echo "Restore completed successfully!\n";
} else {
    echo "[ERROR] Failed to extract archive. Code: {$returnCode}\n";
    exit(1);
}
