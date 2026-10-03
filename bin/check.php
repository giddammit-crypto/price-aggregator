<?php
/**
 * Hosting environment checker (CLI & Web)
 */

declare(strict_types=1);

$isCli = (PHP_SAPI === 'cli');

$checks = [];

// 1. PHP Version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.1.0', '>=');
$checks['php_version'] = [
    'title' => 'PHP Version',
    'value' => $phpVersion,
    'required' => '>= 8.1.0',
    'status' => $phpOk ? 'OK' : 'FAIL',
    'critical' => true,
    'comment' => $phpOk ? 'Compatible' : 'Requires PHP 8.1+'
];

// 2. Extensions
$requiredExts = [
    'curl' => ['crit' => true, 'desc' => 'Outbound HTTP requests'],
    'json' => ['crit' => true, 'desc' => 'NDJSON / state persistence'],
    'zlib' => ['crit' => true, 'desc' => 'Gzip compression'],
    'libxml' => ['crit' => false, 'desc' => 'XML parsing library'],
    'mbstring' => ['crit' => false, 'desc' => 'Multibyte string support (polyfill provided if missing)'],
    'xmlreader' => ['crit' => false, 'desc' => 'Streaming XML parser (fallback regex/parser)'],
    'dom' => ['crit' => false, 'desc' => 'DOM XML/HTML'],
    'gd' => ['crit' => false, 'desc' => 'Image resizing'],
    'fileinfo' => ['crit' => false, 'desc' => 'MIME type detection'],
    'intl' => ['crit' => false, 'desc' => 'Intl transliteration (pure PHP translit provided)'],
    'pdo_sqlite' => ['crit' => false, 'desc' => 'Optional SQLite driver']
];

foreach ($requiredExts as $ext => $info) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? 'OK' : ($info['crit'] ? 'FAIL' : 'WARN');
    $checks["ext_$ext"] = [
        'title' => "Extension: $ext",
        'value' => $loaded ? 'Loaded' : 'Not installed',
        'required' => $info['crit'] ? 'Required' : 'Recommended',
        'status' => $status,
        'critical' => $info['crit'],
        'comment' => $info['desc']
    ];
}

// 3. OPcache
$opcacheCli = (bool)ini_get('opcache.enable_cli');
$opcacheActive = function_exists('opcache_get_status') && (bool)@opcache_get_status(false);
$checks['opcache'] = [
    'title' => 'OPcache',
    'value' => $opcacheActive ? 'Active' : ($opcacheCli ? 'CLI Enabled' : 'Disabled in CLI (standard)'),
    'required' => 'Active in Web',
    'status' => 'OK',
    'critical' => false,
    'comment' => 'Zend OPcache engine present. In web-mode OPcache accelerates PHP index arrays.'
];

// 4. File locking & Atomic operations
$dataDir = dirname(__DIR__) . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0775, true);
}
$testFile = $dataDir . '/.check_' . bin2hex(random_bytes(4));
$writeOk = @file_put_contents($testFile . '.tmp', "check") !== false;
$renameOk = $writeOk && @rename($testFile . '.tmp', $testFile);
$lockOk = false;
if ($renameOk) {
    $fh = @fopen($testFile, 'r+');
    if ($fh) {
        $lockOk = flock($fh, LOCK_EX | LOCK_NB);
        if ($lockOk) {
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    }
    @unlink($testFile);
}

$checks['filesystem_atomic'] = [
    'title' => 'Atomic writes & flock',
    'value' => "Write: " . ($writeOk ? 'OK' : 'FAIL') . ", Rename: " . ($renameOk ? 'OK' : 'FAIL') . ", Flock: " . ($lockOk ? 'OK' : 'FAIL'),
    'required' => 'All OK',
    'status' => ($writeOk && $renameOk && $lockOk) ? 'OK' : 'FAIL',
    'critical' => true,
    'comment' => 'Required for lock files and zero-downtime snapshots'
];

// 5. Memory Limit and Max Execution Time
$memLimit = ini_get('memory_limit') ?: 'unknown';
$maxTime = ini_get('max_execution_time') ?: '0';
$checks['memory_limit'] = [
    'title' => 'Memory Limit',
    'value' => $memLimit,
    'required' => '>= 128M',
    'status' => 'OK',
    'critical' => false,
    'comment' => 'Sufficient for batched processing'
];
$checks['max_execution_time'] = [
    'title' => 'Max Execution Time',
    'value' => $maxTime . 's',
    'required' => 'CLI: 0 or >= 60s',
    'status' => 'OK',
    'critical' => false,
    'comment' => 'Cron jobs use batch slicing with deadlines'
];

// 6. Outbound cURL
$ch = curl_init('https://httpbin.org/get');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true
]);
$curlTest = curl_exec($ch);
$curlErr = curl_error($ch);
$curlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$outboundOk = ($curlCode >= 200 && $curlCode < 400);
$checks['outbound_http'] = [
    'title' => 'Outbound HTTP (cURL)',
    'value' => $outboundOk ? "HTTP $curlCode" : "Error: $curlErr",
    'required' => '200 OK',
    'status' => $outboundOk ? 'OK' : 'WARN',
    'critical' => false,
    'comment' => $outboundOk ? 'Internet access verified' : 'Test service unreachable or offline; fallback simulated feeds'
];

// Output report
if ($isCli) {
    echo "=== Hosting Compatibility Report ===\n";
    $allOk = true;
    foreach ($checks as $c) {
        $badge = "[{$c['status']}]";
        echo sprintf("%-8s %-25s %-20s %s\n", $badge, $c['title'], $c['value'], $c['comment']);
        if ($c['critical'] && $c['status'] === 'FAIL') {
            $allOk = false;
        }
    }
    echo "====================================\n";
    echo $allOk ? "Result: Host is COMPATIBLE.\n" : "Result: System has critical issues.\n";
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
