<?php
declare(strict_types=1);

/**
 * Cron Job: check_alerts
 * Evaluates active price drop alert subscriptions and triggers notifications.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', dirname(__DIR__, 2) . '/app');
require_once dirname(__DIR__, 2) . '/app/helpers.php';

$root = dirname(__DIR__, 2);
\App\Core\Config::init($root . '/config');
\App\Core\Logger::init($root . '/data/logs');
\App\Storage\Snapshot::init($root . '/data/snapshots');
\App\Storage\Pack::init($root . '/data/catalog/products');

$subsRepo = new \App\Storage\Repositories\FileSubsRepository();
$prodRepo = new \App\Storage\Repositories\FileProductRepository();

$activeSubs = $subsRepo->getAllActive();
$triggered = 0;
$checked = 0;

$alertsLog = $root . '/data/logs/alerts_sent.ndjson';

foreach ($activeSubs as $sub) {
    $checked++;
    $productId = (int)$sub['product_id'];
    $targetPrice = (float)$sub['target_price'];

    $prod = $prodRepo->find($productId);
    if (!$prod) {
        continue;
    }

    $currentMinPrice = (float)($prod['min_price'] ?? 0);
    if ($currentMinPrice > 0 && $currentMinPrice <= $targetPrice) {
        $triggered++;
        $entry = [
            'date' => date('Y-m-d H:i:s'),
            'email' => $sub['email'],
            'product_id' => $productId,
            'product_title' => $prod['title'],
            'target_price' => $targetPrice,
            'current_price' => $currentMinPrice,
            'url' => url('/p/' . ($prod['slug'] ?? 'prod') . '-' . $productId)
        ];
        \App\Storage\Fs::appendLine($alertsLog, (string)json_encode($entry, JSON_UNESCAPED_UNICODE));
    }
}

echo sprintf("[%s] check_alerts finished: checked %d subs, triggered %d price alerts.\n", date('Y-m-d H:i:s'), $checked, $triggered);
