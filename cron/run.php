<?php
declare(strict_types=1);

/**
 * PriceHub Cron Runner & Job Dispatcher
 *
 * Usage:
 *   php cron/run.php dispatch       # Dispatches due jobs according to schedule
 *   php cron/run.php [job_name]     # Runs specific job directly (e.g. build_index, fx_rates)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden: CLI execution only.\n";
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

\App\Core\Config::init($root . '/config');
\App\Core\Logger::init($root . '/data/logs');

$locksDir = $root . '/data/state/locks';
$cursorsDir = $root . '/data/state/cursors';

if (!is_dir($locksDir)) {
    @mkdir($locksDir, 0775, true);
}
if (!is_dir($cursorsDir)) {
    @mkdir($cursorsDir, 0775, true);
}

$action = $argv[1] ?? 'dispatch';

$jobsSchedule = [
    'fx_rates' => [
        'interval' => 86400, // 24 hours
        'file' => $root . '/cron/jobs/fx_rates.php',
    ],
    'check_alerts' => [
        'interval' => 900,   // 15 minutes
        'file' => $root . '/cron/jobs/check_alerts.php',
    ],
    'ingest_feeds' => [
        'interval' => 7200,  // 2 hours
        'file' => $root . '/cron/jobs/ingest_feeds.php',
    ],
    'build_index' => [
        'interval' => 21600, // 6 hours
        'file' => $root . '/cron/jobs/build_index.php',
    ],
    'cleanup_logs' => [
        'interval' => 86400, // 24 hours
        'file' => $root . '/cron/jobs/cleanup_logs.php',
    ],
];

function runJobWithLock(string $jobName, string $jobFile, string $locksDir, string $cursorsDir): bool
{
    $lockFile = $locksDir . '/' . $jobName . '.lock';
    $fp = @fopen($lockFile, 'c+');
    if (!$fp) {
        echo "[ERROR] Cannot open lock file for {$jobName}\n";
        return false;
    }

    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        echo "[SKIP] Job '{$jobName}' is already running.\n";
        fclose($fp);
        return false;
    }

    echo "=== Running Job: {$jobName} [" . date('Y-m-d H:i:s') . "] ===\n";
    $startTime = microtime(true);

    try {
        $phpBin = file_exists('/home/astra/.local/bin/php') ? '/home/astra/.local/bin/php' : (PHP_BINARY ?: 'php');
        passthru(escapeshellcmd($phpBin) . ' ' . escapeshellarg($jobFile), $returnVar);

        $duration = round(microtime(true) - $startTime, 3);
        $cursorFile = $cursorsDir . '/' . $jobName . '.json';
        $cursorData = [
            'job' => $jobName,
            'last_run' => time(),
            'last_run_date' => date('Y-m-d H:i:s'),
            'duration_sec' => $duration,
            'exit_code' => $returnVar
        ];
        \App\Storage\Fs::atomicWrite($cursorFile, (string)json_encode($cursorData, JSON_PRETTY_PRINT));
        echo "=== Finished Job: {$jobName} (exit: {$returnVar}) in {$duration}s ===\n\n";
        return $returnVar === 0;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

if ($action === 'dispatch') {
    echo "--- Dispatcher starting [" . date('Y-m-d H:i:s') . "] ---\n";
    $timeLimit = 50; // Max 50s budget per cron tick
    $start = time();

    foreach ($jobsSchedule as $name => $cfg) {
        if ((time() - $start) > $timeLimit) {
            echo "[TIME BUDGET] Exceeded {$timeLimit}s, stopping dispatching for this tick.\n";
            break;
        }

        $cursorFile = $cursorsDir . '/' . $name . '.json';
        $lastRun = 0;
        if (file_exists($cursorFile)) {
            $cData = @json_decode((string)file_get_contents($cursorFile), true);
            $lastRun = (int)($cData['last_run'] ?? 0);
        }

        $elapsed = time() - $lastRun;
        if ($elapsed >= $cfg['interval']) {
            runJobWithLock($name, $cfg['file'], $locksDir, $cursorsDir);
        } else {
            $nextIn = $cfg['interval'] - $elapsed;
            echo "[IDLE] Job '{$name}' not due yet (next in {$nextIn}s).\n";
        }
    }
    echo "--- Dispatcher completed ---\n";
    exit(0);
}

// Single job direct execution
if (isset($jobsSchedule[$action])) {
    $ok = runJobWithLock($action, $jobsSchedule[$action]['file'], $locksDir, $cursorsDir);
    exit($ok ? 0 : 1);
} else {
    echo "Unknown job: '{$action}'. Available jobs: " . implode(', ', array_keys($jobsSchedule)) . ", dispatch\n";
    exit(1);
}
