<?php
declare(strict_types=1);

/**
 * Cron Job: fx_rates
 * Updates daily CBR exchange rates (USD, EUR, CNY, BYN, KZT).
 */

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', dirname(__DIR__, 2) . '/app');
require_once dirname(__DIR__, 2) . '/app/helpers.php';

$root = dirname(__DIR__, 2);
\App\Core\Logger::init($root . '/data/logs');

$service = new \App\Ingest\FxRatesService();
$ok = $service->updateRates();

$rates = $service->getRates();
echo sprintf(
    "[%s] fx_rates finished. USD: %.2f, EUR: %.2f, CNY: %.2f. Status: %s\n",
    date('Y-m-d H:i:s'),
    $rates['USD'] ?? 0,
    $rates['EUR'] ?? 0,
    $rates['CNY'] ?? 0,
    $ok ? 'OK' : 'FALLBACK'
);
