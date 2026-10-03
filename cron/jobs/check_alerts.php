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

foreach ($activeSubs as $sub) {
    $checked++;
    $productId = (int)$sub['product_id'];
    $prod = $prodRepo->findById($productId);
    if (\App\Services\PriceAlertService::isEligible($sub, $prod)) {
        $triggered++;
    }
}

// There is no configured mail delivery or confirmation route yet. Never log
// private addresses as "sent" or imply that a notification was delivered.
echo sprintf("[%s] check_alerts finished: checked %d subs, %d eligible (delivery not configured).\n", date('Y-m-d H:i:s'), $checked, $triggered);
