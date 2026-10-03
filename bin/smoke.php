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

Config::init($root . '/config');
View::init($root . '/resources/views');
Logger::init($root . '/data/logs');
RateLimiter::init($root . '/data/state/ratelimit');
// Match public/index.php: the storage classes must work without CLI-only setup.

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
$router->get('/api/catalog-filter', [\App\Controllers\CatalogController::class, 'filterPartial']);
$router->post('/api/subscribe', [\App\Controllers\SubscribeController::class, 'subscribe']);
$router->get('/pages/how-it-works', [\App\Controllers\PagesController::class, 'howItWorks']);
$router->get('/pages/marketplaces', [\App\Controllers\PagesController::class, 'marketplaces']);
$router->get('/pages/report', [\App\Controllers\PagesController::class, 'reportForm']);
$router->post('/pages/report', [\App\Controllers\PagesController::class, 'reportSubmit']);
$router->get('/pages/legal', [\App\Controllers\PagesController::class, 'legal']);
$router->get('/ui-kit', [\App\Controllers\PagesController::class, 'uiKit']);
$router->get('/sitemap.xml', [\App\Controllers\PagesController::class, 'sitemap']);
$router->get('/robots.txt', [\App\Controllers\PagesController::class, 'robots']);
$router->get('/admin', [\App\Admin\AdminController::class, 'index']);
$router->get('/admin/login', [\App\Admin\AdminController::class, 'loginForm']);
$router->post('/admin/login', [\App\Admin\AdminController::class, 'loginSubmit']);
$router->get('/admin/shops', [\App\Admin\AdminController::class, 'shops']);
$router->get('/admin/matching', [\App\Admin\AdminController::class, 'matchingQueue']);
$router->post('/admin/matching/resolve', [\App\Admin\AdminController::class, 'matchingResolve']);
$router->get('/admin/reports', [\App\Admin\AdminController::class, 'reports']);
$router->get('/admin/health', [\App\Admin\AdminController::class, 'health']);

$routesToTest = [
    ['GET', '/', 200, 'PriceHub'],
    ['GET', '/catalog/graphics-cards', 200, 'Видеокарты'],
    ['GET', '/catalog/smartphones', 200, 'Смартфоны'],
    ['GET', '/catalog/smartphones?page=99999999999999999999', 200, 'Смартфоны'],
    ['GET', '/p/product-1', 301, ''], // wrong slug must not render a false-200 product
    ['GET', '/p/' . (\App\Storage\Pack::get(1)['slug'] ?? 'missing') . '-1', 200, 'Где купить'],
    ['GET', '/search?q=rtx', 200, 'Поиск по запросу'],
    ['GET', '/search?q=сяоми', 200, 'Поиск по запросу'],
    ['GET', '/search?q=ghjwtccjh', 200, 'Поиск по запросу'], // layout fix
    ['GET', '/compare', 200, 'Сравнение товаров'],
    ['GET', '/favorites', 200, 'Избранные товары'],
    ['GET', '/compare?ids=1', 200, 'Сравнение товаров'],
    ['GET', '/favorites?ids=1', 200, 'Избранные товары'],
    ['GET', '/api/suggest?q=asus', 200, 'suggestions'],
    ['GET', '/api/history/1', 200, 'min'],
    ['GET', '/api/history/0', 400, 'Invalid product ID'],
    ['GET', '/api/catalog-filter?catId=30&sort=price_asc', 200, 'count'],
    ['GET', '/api/catalog-filter?catId=-1', 404, 'Категория не найдена'],
    ['POST', '/api/subscribe', 403, 'CSRF'],
    ['GET', '/pages/how-it-works', 200, 'Как мы считаем'],
    ['GET', '/pages/marketplaces', 200, 'О маркетплейсах'],
    ['GET', '/pages/report', 200, 'Сообщить'],
    ['POST', '/pages/report', 403, 'CSRF'],
    ['GET', '/pages/legal', 200, '152-ФЗ'],
    ['GET', '/ui-kit', 200, 'UI-Кит'],
    ['GET', '/sitemap.xml', 200, '<urlset'],
    ['GET', '/robots.txt', 200, 'Disallow: /go/'],
    ['GET', '/go/1/citilink%3A1_0', 302, ''],
    ['GET', '/go/search/dns?q=rtx', 302, ''],
    ['GET', '/admin/login', 200, 'Вход в панель'],
    ['GET', '/admin', 302, ''],
    ['GET', '/admin/shops', 302, ''],
    ['GET', '/admin/matching', 302, ''],
    ['POST', '/admin/matching/resolve', 401, 'Unauthorized'],
    ['GET', '/admin/reports', 302, ''],
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
    $ok = ($status === $expectedStatus) && ($expectedNeedle === '' || str_contains($content, $expectedNeedle));

    $badge = $ok ? "[OK]" : "[FAIL]";
    echo sprintf("%-6s %-32s status: %d (expected %d) in %6.2f ms\n", $badge, $uri, $status, $expectedStatus, $durationMs);

    if (!$ok) {
        $allPassed = false;
        echo "   -> Content didn't contain '{$expectedNeedle}'\n";
    }
}

echo "===============================================\n";
if ($allPassed) {
    echo "Result: All in-process route smoke tests PASSED (not an HTTP latency benchmark).\n";
} else {
    echo "Result: Some smoke tests failed.\n";
    exit(1);
}
