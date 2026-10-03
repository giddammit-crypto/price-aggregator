<?php
declare(strict_types=1);

/**
 * Cron Job: cleanup_logs
 * Cleans up old snapshots (keeps last 3), stale locks, and logs older than 30 days.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', dirname(__DIR__, 2) . '/app');
require_once dirname(__DIR__, 2) . '/app/helpers.php';

$root = dirname(__DIR__, 2);
\App\Core\Logger::init($root . '/data/logs');
\App\Storage\Snapshot::init($root . '/data/snapshots');

// 1. Keep only last 3 snapshots
$snapshotsCleaned = \App\Storage\Snapshot::cleanup(3);

// 2. Clean stale locks older than 1 hour
$locksDir = $root . '/data/state/locks';
$locksCleaned = 0;
if (is_dir($locksDir)) {
    foreach (glob($locksDir . '/*.lock') ?: [] as $lockFile) {
        if (time() - filemtime($lockFile) > 3600) {
            @unlink($lockFile);
            $locksCleaned++;
        }
    }
}

// 3. Clean click logs older than 30 days
$clicksDir = $root . '/data/logs/clicks';
$clicksCleaned = 0;
if (is_dir($clicksDir)) {
    $cutoff = date('Y-m-d', strtotime('-30 days'));
    foreach (glob($clicksDir . '/*.ndjson') ?: [] as $f) {
        $base = basename($f, '.ndjson');
        if ($base < $cutoff) {
            @unlink($f);
            $clicksCleaned++;
        }
    }
}

echo sprintf(
    "[%s] cleanup_logs finished: %d old snapshots, %d stale locks, %d old log files removed.\n",
    date('Y-m-d H:i:s'),
    $snapshotsCleaned,
    $locksCleaned,
    $clicksCleaned
);
