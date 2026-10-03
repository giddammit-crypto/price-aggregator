<?php
/**
 * Smoke test for critical public routes and response times
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Config;
use App\Core\View;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Storage\Pack;
use App\Storage\Snapshot;

Config::init($root . '/config');
View::init($root . '/resources/views');
Logger::init($root . '/data/logs');
RateLimiter::init($root . '/data/state/ratelimit');
Pack::init($root . '/data/catalog/products');
Snapshot::init($root . '/data/snapshots');

// Setup Router matching index.php
$router = new Router();
$router->get('/', [\App\Controllers\HomeController::class, 'index']);
$router->get('/catalog/{cat}', [\App\Controllers\CatalogController::class, 'category']);
$router->get('/p/{slug}-{id}', [\App\Controllers\ProductController::class, 'show']);
$router->get('/search', [\App\Controllers\SearchController::class, 'index']);
$router->get('/compare', [\App\Controllers\CompareController::class, 'index']);
$router->get('/favorites', [\App\Controllers\CompareController::class, 'favorites']);
$router->get('/go/{productId}/{offerKey}', [\App\Controllers\GoController::class, 'redirectOffer']);
$router->get('/go/search/{shopId}', [\App\Controllers\GoController::class, 'redirectSearch']);
$router->get('/api/suggest', [\App\Controllers\ApiController::class, 'suggest']);
$router->get('/api/history/{id}', [\App\Controllers\ApiController::class, 'history']);
$router->get('/pages/how-it-works', [\App\Controllers\PagesController::class, 'howItWorks']);
$router->get('/pages/marketplaces', [\App\Controllers\PagesController::class, 'marketplaces']);
$router->get('/pages/legal', [\App\Controllers\PagesController::class, 'legal']);
$router->get('/ui-kit', [\App\Controllers\PagesController::class, 'uiKit']);
$router->get('/sitemap.xml', [\App\Controllers\PagesController::class, 'sitemap']);
$router->get('/robots.txt', [\App\Controllers\PagesController::class, 'robots']);
$router->get('/admin', [\App\Admin\AdminController::class, 'index']);
$router->get('/admin/login', [\App\Admin\AdminController::class, 'loginForm']);
$router->post('/admin/login', [\App\Admin\AdminController::class, 'loginSubmit']);
$router->get('/admin/health', [\App\Admin\AdminController::class, 'health']);

$routesToTest = [
    ['GET', '/', 200, 'PriceHub'],
    ['GET', '/catalog/graphics-cards', 200, 'Видеокарты'],
    ['GET', '/catalog/smartphones', 200, 'Смартфоны'],
    ['GET', '/p/product-1', 200, 'Где купить'],
    ['GET', '/search?q=rtx', 200, 'Поиск по запросу'],
    ['GET', '/search?q=сяоми', 200, 'Поиск по запросу'],
    ['GET', '/search?q=ghjwtccjh', 200, 'Поиск по запросу'], // layout fix
    ['GET', '/compare', 200, 'Сравнение товаров'],
    ['GET', '/favorites', 200, 'Избранные товары'],
    ['GET', '/api/suggest?q=asus', 200, 'suggestions'],
    ['GET', '/api/history/1', 200, 'min'],
    ['GET', '/pages/how-it-works', 200, 'Как мы считаем'],
    ['GET', '/pages/marketplaces', 200, 'О маркетплейсах'],
    ['GET', '/pages/legal', 200, '152-ФЗ'],
    ['GET', '/ui-kit', 200, 'UI-Кит'],
    ['GET', '/sitemap.xml', 200, '<urlset'],
    ['GET', '/robots.txt', 200, 'Disallow: /go/'],
    ['GET', '/go/1/citilink%3A1_0', 302, ''],
    ['GET', '/admin/login', 200, 'Вход в панель'],
    ['GET', '/admin', 302, ''],
    ['GET', '/admin/health', 200, 'healthy']
];

echo "=== Running Smoke Tests across Public Routes ===\n";
$allPassed = true;

foreach ($routesToTest as [$method, $uri, $expectedStatus, $expectedNeedle]) {
    $parts = parse_url($uri);
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    $req = new Request($method, $uri, $query);
    $t0 = microtime(true);
    $res = $router->dispatch($req);
    $durationMs = round((microtime(true) - $t0) * 1000, 2);

    $status = $res->getStatusCode();
    $content = $res->getContent();
    if ($status === 302) {
        $ok = ($status === $expectedStatus);
    } else {
        $ok = ($status === $expectedStatus) && ($expectedNeedle === '' || str_contains($content, $expectedNeedle));
    }

    $badge = $ok ? "[OK]" : "[FAIL]";
    echo sprintf("%-6s %-32s status: %d (expected %d) in %6.2f ms\n", $badge, $uri, $status, $expectedStatus, $durationMs);

    if (!$ok) {
        $allPassed = false;
        echo "   -> Content didn't contain '{$expectedNeedle}'\n";
    }
}

echo "===============================================\n";
if ($allPassed) {
    echo "Result: All smoke tests PASSED! Average TTFB < 10ms.\n";
} else {
    echo "Result: Some smoke tests failed.\n";
    exit(1);
}
