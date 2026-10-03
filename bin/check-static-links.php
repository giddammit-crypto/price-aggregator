<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');

$directory = $argv[1] ?? ($root . '/gh-pages-export');
$result = \App\Services\StaticProductLinks::check($directory);
printf("Pages: %d; product pages: %d; product links: %d; unique targets: %d; broken: %d\n",
    $result['pages'], $result['product_pages'], $result['links'], $result['unique_product_links'] ?? 0, count($result['errors']));
foreach (array_slice($result['errors'], 0, 15) as $error) {
    echo "[FAIL] {$error}\n";
}
if (count($result['errors']) > 15) {
    echo '... and ' . (count($result['errors']) - 15) . " more\n";
}
exit($result['errors'] ? 1 : 0);
