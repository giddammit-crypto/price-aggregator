<?php
declare(strict_types=1);

// Controller and storage regression checks in an isolated fixture (no seed or live data edits).
$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Controllers\ApiController;
use App\Admin\AdminController;
use App\Controllers\CatalogController;
use App\Controllers\CompareController;
use App\Controllers\GoController;
use App\Controllers\ProductController;
use App\Controllers\SubscribeController;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\PriceAlertService;
use App\Storage\Fs;
use App\Storage\Pack;
use App\Storage\Snapshot;

function checkRoute(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$tmp = sys_get_temp_dir() . '/pricehub-routes-' . bin2hex(random_bytes(6));
$snapDir = $tmp . '/snapshots';
$packDir = $tmp . '/products';
$snapId = 'test';
mkdir($snapDir . '/' . $snapId . '/cat', 0775, true);
mkdir($packDir, 0775, true);

try {
    Config::init($root . '/config');
    View::init($root . '/resources/views');
    Pack::init($packDir);
    Snapshot::init($snapDir);

    $products = [
        ['id' => 1, 'pub' => 1, 'cat' => 30, 'brand' => 'Fixture', 'title' => 'Fixture Phone', 'slug' => 'fixture-phone', 'agg' => ['min' => 1000, 'cnt' => 1], 'offers' => [['k' => 'bad', 'shop' => 'dns', 'url' => 'javascript:alert(1)']]],
        ['id' => 257, 'pub' => 1, 'cat' => 30, 'brand' => 'Other', 'title' => 'Fixture Other', 'slug' => 'fixture-other', 'agg' => ['min' => 2000, 'cnt' => 1]],
    ];
    checkRoute(Pack::writePack('p001', $products), 'fixture pack write');
    $rows = [
        ['id' => 1, 'b' => 'Fixture', 'p' => 1000, 'pop' => 2, 'd' => 0, 'mp' => 1, 'cb' => 0, 't' => 'Fixture Phone', 'slug' => 'fixture-phone'],
        ['id' => 257, 'b' => 'Other', 'p' => 2000, 'pop' => 1, 'd' => 0, 'mp' => 0, 'cb' => 1, 't' => 'Fixture Other', 'slug' => 'fixture-other'],
    ];
    Fs::atomicWritePhpArray($snapDir . '/' . $snapId . '/categories.php', [30 => ['slug' => 'smartphones', 'name' => 'Смартфоны']]);
    Fs::atomicWritePhpArray($snapDir . '/' . $snapId . '/cat/30.php', $rows);
    Fs::atomicWritePhpArray($snapDir . '/' . $snapId . '/cat/30.facets.php', ['brand' => ['Fixture' => 1, 'Other' => 1]]);
    checkRoute(Snapshot::publish($snapId), 'fixture snapshot publish');

    $catalog = new CatalogController();
    $req = new Request('GET', '/api/catalog-filter', ['catId' => '30', 'brand' => 'Fixture']);
    $response = $catalog->filterPartial($req);
    $data = json_decode($response->getContent(), true);
    checkRoute($response->getStatusCode() === 200 && $data['count'] === 1 && str_contains($data['html'], 'Fixture Phone'), 'brand filtering');
    $req = new Request('GET', '/api/catalog-filter', ['catId' => '30', 'seller' => 'crossborder', 'price_to' => '1500']);
    checkRoute(json_decode($catalog->filterPartial($req)->getContent(), true)['count'] === 0, 'seller and price filtering');
    checkRoute($catalog->filterPartial(new Request('GET', '/api/catalog-filter', ['catId' => '../30']))->getStatusCode() === 404, 'unknown category must 404');
    checkRoute($catalog->category(new Request('GET', '/catalog/smartphones', ['page' => PHP_INT_MAX]), ['cat' => 'smartphones'])->getStatusCode() === 200, 'extreme page should not overflow');

    $compare = new CompareController();
    checkRoute(str_contains($compare->index(new Request('GET', '/compare', ['ids' => '1,257']))->getContent(), 'Fixture Phone'), 'compare product lookup');
    checkRoute(str_contains($compare->favorites(new Request('GET', '/favorites', ['ids' => '1']))->getContent(), 'Fixture Phone'), 'favorites product lookup');
    checkRoute((new ApiController())->history(new Request(), ['id' => '0'])->getStatusCode() === 400, 'history invalid id');
    checkRoute((new ProductController())->show(new Request(), ['id' => '1'])->getStatusCode() === 200, 'product lookup');
    checkRoute((new ProductController())->show(new Request(), ['id' => '1abc', 'slug' => 'fixture-phone'])->getStatusCode() === 404, 'malformed product ID must not show another product');
    checkRoute((new ProductController())->show(new Request(), ['id' => '1', 'slug' => 'wrong-product'])->getStatusCode() === 301, 'incorrect slug redirects to canonical product');

    $redirect = (new GoController())->redirectOffer(new Request(), ['productId' => '1', 'offerKey' => 'bad']);
    checkRoute($redirect->getStatusCode() === 302, 'invalid outbound URL fallback');

    $xml = Response::html('<urlset/>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    $getHeaders = new ReflectionProperty(Response::class, 'headers');
    checkRoute($getHeaders->getValue($redirect)['Location'] === '/p/fixture-phone-1', 'unsafe URL must not be used as Location');
    checkRoute($getHeaders->getValue($xml)['Content-Type'] === 'application/xml; charset=UTF-8', 'sitemap XML Content-Type');

    $request = new Request('POST', '/api/subscribe', [], [], ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4']);
    checkRoute($request->getClientIp() === '127.0.0.1', 'untrusted forwarded IP must not bypass rate limit');
    checkRoute($request->getPost('_csrf', '') === '', 'explicit empty POST must not leak globals');
    $subscriber = new SubscribeController();
    checkRoute($subscriber->subscribe($request)->getStatusCode() === 403, 'subscription CSRF');
    $token = Csrf::getToken();
    checkRoute($subscriber->subscribe(new Request('POST', '/api/subscribe', [], ['_csrf' => $token, 'email' => 'person@example.invalid']))->getStatusCode() === 400, 'subscription requires product and target');
    checkRoute($subscriber->subscribe(new Request('POST', '/api/subscribe', [], ['_csrf' => $token, 'email' => 'person@example.invalid', 'product_id' => 1, 'target_price' => 900]))->getStatusCode() === 503, 'no fake email success before mail is configured');

    checkRoute(PriceAlertService::isEligible(['product_id' => 1, 'target_price' => 1200], $products[0]), 'eligible alert reads agg.min');
    checkRoute(!PriceAlertService::isEligible(['product_id' => 1, 'target_price' => 900], $products[0]), 'alert ignores prices above threshold');
    checkRoute(!PriceAlertService::isEligible(['product_id' => 1, 'target_price' => 1200], array_merge($products[0], ['pub' => 0])), 'alert ignores unpublished product');
    checkRoute(!PriceAlertService::isEligible(['product_id' => 257, 'target_price' => 1200], $products[0]), 'alert product ID must match');

    // Authentication must persist across separate controller/request instances.
    Config::set('secrets.admin_password_hash', password_hash('isolated-test-password', PASSWORD_DEFAULT));
    $admin = new AdminController();
    checkRoute($admin->index(new Request())->getStatusCode() === 302, 'anonymous admin access');
    checkRoute($admin->loginSubmit(new Request('POST', '/admin/login', [], ['password' => 'isolated-test-password']))->getStatusCode() === 302, 'admin login');
    checkRoute((new AdminController())->index(new Request())->getStatusCode() === 200, 'admin session persistence');
    checkRoute($admin->matchingResolve(new Request('POST', '/admin/matching/resolve', [], ['offer_key' => 'example', 'product_id' => 1]))->getStatusCode() === 403, 'admin override CSRF');
    checkRoute($admin->logout(new Request())->getStatusCode() === 302, 'admin logout');
    checkRoute($admin->index(new Request())->getStatusCode() === 302, 'admin logout invalidates access');

    echo "[PASS] Fixture routes, filters, product, comparison, redirects, headers, auth boundaries\n";
} finally {
    foreach (glob($snapDir . '/' . $snapId . '/cat/*') ?: [] as $file) unlink($file);
    foreach (glob($snapDir . '/' . $snapId . '/*') ?: [] as $file) if (is_file($file)) unlink($file);
    foreach (glob($packDir . '/*') ?: [] as $file) unlink($file);
    @unlink($snapDir . '/CURRENT');
    @rmdir($snapDir . '/' . $snapId . '/cat');
    @rmdir($snapDir . '/' . $snapId);
    @rmdir($snapDir);
    @rmdir($packDir);
    @rmdir($tmp);
}
