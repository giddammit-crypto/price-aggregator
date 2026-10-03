<?php
declare(strict_types=1);

/**
 * Backup utility for PriceHub
 * Packs config, snapshots, catalog, and state into tar.gz
 */

$root = dirname(__DIR__);
$backupDir = $root . '/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0775, true);
}

$dateStr = date('Ymd_His');
$archivePath = "{$backupDir}/backup_{$dateStr}.tar.gz";

echo "Creating backup: {$archivePath}...\n";

$cmd = sprintf(
    "tar -czf %s --exclude='data/state/locks/*' --exclude='data/logs/*' -C %s config data/catalog data/history data/subs data/state",
    escapeshellarg($archivePath),
    escapeshellarg($root)
);

exec($cmd, $output, $returnCode);

if ($returnCode === 0 && file_exists($archivePath)) {
    $sizeMb = round(filesize($archivePath) / 1048576, 2);
    echo "[OK] Backup created successfully: {$archivePath} ({$sizeMb} MB)\n";

    // Clean old backups keeping last 7
    $files = glob("{$backupDir}/backup_*.tar.gz") ?: [];
    if (count($files) > 7) {
        sort($files);
        $toRemove = array_slice($files, 0, count($files) - 7);
        foreach ($toRemove as $f) {
            @unlink($f);
            echo "Cleaned old backup: {$f}\n";
        }
    }
} else {
    echo "[ERROR] Failed to create backup. Code: {$returnCode}\n";
    exit(1);
}
