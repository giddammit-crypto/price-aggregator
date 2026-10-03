<?php
/**
 * PriceHub Front Controller
 */

declare(strict_types=1);

$rootPath = dirname(__DIR__);

require_once $rootPath . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $rootPath . '/app');

require_once $rootPath . '/app/helpers.php';

// Load environment variables if available
\App\Core\Env::load($rootPath . '/.env');

// Initialize Core components
\App\Core\Config::init($rootPath . '/config');
\App\Core\View::init($rootPath . '/resources/views');
\App\Core\Logger::init($rootPath . '/data/logs');
\App\Core\RateLimiter::init($rootPath . '/data/state/ratelimit');

$debug = (bool)\App\Core\Config::get('app.debug', false);
\App\Core\ErrorHandler::register($debug);

// Create HTTP Request
$request = \App\Core\Request::createFromGlobals();

// Global Rate Limiting (120 requests per minute per IP)
$clientIp = $request->getClientIp();
if (!\App\Core\RateLimiter::check($clientIp, 120, 60)) {
    http_response_code(429);
    $view = new \App\Core\View();
    $view->setLayout('layout/main');
    echo $view->render('errors/429', [
        'pageTitle' => '429 — Слишком много запросов'
    ]);
    exit;
}

// Router Setup
$router = new \App\Core\Router();

// Define Routes
$router->get('/', [\App\Controllers\HomeController::class, 'index']);
$router->get('/catalog/{cat}', [\App\Controllers\CatalogController::class, 'category']);
$router->get('/p/{slug}-{id}', [\App\Controllers\ProductController::class, 'show']);
$router->get('/search', [\App\Controllers\SearchController::class, 'index']);
$router->get('/compare', [\App\Controllers\CompareController::class, 'index']);
$router->get('/favorites', [\App\Controllers\CompareController::class, 'favorites']);
$router->get('/go/{productId}/{offerKey}', [\App\Controllers\GoController::class, 'redirectOffer']);
$router->get('/go/search/{shopId}', [\App\Controllers\GoController::class, 'redirectSearch']);

// API Endpoints
$router->get('/api/suggest', [\App\Controllers\ApiController::class, 'suggest']);
$router->get('/api/history/{id}', [\App\Controllers\ApiController::class, 'history']);
$router->get('/api/catalog-filter', [\App\Controllers\CatalogController::class, 'filterPartial']);
$router->post('/api/subscribe', [\App\Controllers\SubscribeController::class, 'subscribe']);

// Static / Trust Pages
$router->get('/pages/how-it-works', [\App\Controllers\PagesController::class, 'howItWorks']);
$router->get('/pages/marketplaces', [\App\Controllers\PagesController::class, 'marketplaces']);
$router->get('/pages/report', [\App\Controllers\PagesController::class, 'reportForm']);
$router->post('/pages/report', [\App\Controllers\PagesController::class, 'reportSubmit']);
$router->get('/pages/legal', [\App\Controllers\PagesController::class, 'legal']);
$router->get('/ui-kit', [\App\Controllers\PagesController::class, 'uiKit']);
$router->get('/sitemap.xml', [\App\Controllers\PagesController::class, 'sitemap']);
$router->get('/robots.txt', [\App\Controllers\PagesController::class, 'robots']);

// Admin Routes
$router->get('/admin', [\App\Admin\AdminController::class, 'index']);
$router->get('/admin/login', [\App\Admin\AdminController::class, 'loginForm']);
$router->post('/admin/login', [\App\Admin\AdminController::class, 'loginSubmit']);
$router->get('/admin/logout', [\App\Admin\AdminController::class, 'logout']);
$router->get('/admin/shops', [\App\Admin\AdminController::class, 'shops']);
$router->get('/admin/matching', [\App\Admin\AdminController::class, 'matchingQueue']);
$router->post('/admin/matching/resolve', [\App\Admin\AdminController::class, 'matchingResolve']);
$router->get('/admin/reports', [\App\Admin\AdminController::class, 'reports']);
$router->get('/admin/health', [\App\Admin\AdminController::class, 'health']);

// 404 Handler
$router->setNotFoundHandler(function (\App\Core\Request $req) {
    if ($req->isAjax()) {
        return \App\Core\Response::json(['error' => 'Not found'], 404);
    }
    $view = new \App\Core\View();
    $view->setLayout('layout/main');
    $html = $view->render('errors/404', [
        'pageTitle' => '404 — Страница не найдена'
    ]);
    return \App\Core\Response::html($html, 404);
});

// Dispatch and Send
$response = $router->dispatch($request);
$response->send();
