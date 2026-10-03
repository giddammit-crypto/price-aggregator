<?php
/**
 * Cron Job: build_index wrapper
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', dirname(__DIR__, 2) . '/app');
require_once dirname(__DIR__, 2) . '/app/helpers.php';

$job = new \App\Services\BuildIndexService(dirname(__DIR__, 2));
echo $job->run() . "\n";
