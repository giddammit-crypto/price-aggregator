<?php
declare(strict_types=1);

/**
 * Static Site Generator for GitHub Pages
 * Exports dynamic PHP pages into static HTML/JSON with client-side interactive search.
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Core\Config;
use App\Core\View;
use App\Core\Request;
use App\Storage\Pack;
use App\Storage\Snapshot;

Config::init($root . '/config');
View::init($root . '/resources/views');
Pack::init($root . '/data/catalog/products');
Snapshot::init($root . '/data/snapshots');

$exportDir = $root . '/gh-pages-export';
if (is_dir($exportDir)) {
    exec("rm -rf " . escapeshellarg($exportDir));
}
@mkdir($exportDir, 0775, true);

$basePrefix = '/price-aggregator';

function transformHtml(string $html, string $basePrefix): string
{
    $replacements = [
        'href="/assets/' => 'href="' . $basePrefix . '/assets/',
        'src="/assets/' => 'src="' . $basePrefix . '/assets/',
        'href="/catalog/' => 'href="' . $basePrefix . '/catalog/',
        'href="/search' => 'href="' . $basePrefix . '/search',
        'href="/compare' => 'href="' . $basePrefix . '/compare',
        'href="/favorites' => 'href="' . $basePrefix . '/favorites',
        'href="/ui-kit' => 'href="' . $basePrefix . '/ui-kit',
        'href="/pages/' => 'href="' . $basePrefix . '/pages/',
        'href="/"' => 'href="' . $basePrefix . '/"',
        'action="/search"' => 'action="' . $basePrefix . '/search/"',
        'action="/api/subscribe"' => 'action="#" onsubmit="alert(\'Подписка успешно оформлена!\'); return false;"',
    ];

    $html = str_replace(array_keys($replacements), array_values($replacements), $html);
    // Guarantee trailing slash on all product links for GitHub Pages
    $html = preg_replace('/href="\/p\/([^"\/]+)\/?("|\')/', 'href="' . $basePrefix . '/p/$1/$2', $html);
    $html = preg_replace('/href="' . preg_quote($basePrefix, '/') . '\/p\/([^"\/]+)\/?("|\')/', 'href="' . $basePrefix . '/p/$1/$2', $html);
    $html = str_replace('xlink:href="/assets/icons/sprite.svg', 'xlink:href="' . $basePrefix . '/assets/icons/sprite.svg', $html);
    $html = str_replace('href="/assets/icons/sprite.svg', 'href="' . $basePrefix . '/assets/icons/sprite.svg', $html);
    $html = preg_replace('/<head>/i', "<head>\n  <base href=\"{$basePrefix}/\">", $html);

    return $html;
}

function savePage(string $dir, string $filename, string $content, string $basePrefix): void
{
    @mkdir($dir, 0775, true);
    $transformed = transformHtml($content, $basePrefix);
    file_put_contents($dir . '/' . $filename, $transformed);
}

echo "1. Exporting Home Page...\n";
$homeController = new \App\Controllers\HomeController();
$resp = $homeController->index(new Request('GET', '/'));
savePage($exportDir, 'index.html', $resp->getContent(), $basePrefix);

echo "2. Exporting Static Trust Pages & UI Kit...\n";
$pagesController = new \App\Controllers\PagesController();

$howItWorks = $pagesController->howItWorks(new Request('GET', '/pages/how-it-works'));
savePage($exportDir . '/pages/how-it-works', 'index.html', $howItWorks->getContent(), $basePrefix);

$marketplaces = $pagesController->marketplaces(new Request('GET', '/pages/marketplaces'));
savePage($exportDir . '/pages/marketplaces', 'index.html', $marketplaces->getContent(), $basePrefix);

$legal = $pagesController->legal(new Request('GET', '/pages/legal'));
savePage($exportDir . '/pages/legal', 'index.html', $legal->getContent(), $basePrefix);

$uiKit = $pagesController->uiKit(new Request('GET', '/ui-kit'));
savePage($exportDir . '/ui-kit', 'index.html', $uiKit->getContent(), $basePrefix);

$compareController = new \App\Controllers\CompareController();
$compare = $compareController->index(new Request('GET', '/compare'));
savePage($exportDir . '/compare', 'index.html', $compare->getContent(), $basePrefix);

$favorites = $compareController->favorites(new Request('GET', '/favorites'));
savePage($exportDir . '/favorites', 'index.html', $favorites->getContent(), $basePrefix);

$err404 = View::make('errors/404', ['pageTitle' => '404 — Страница не найдена'], 'layout/main');
savePage($exportDir, '404.html', $err404, $basePrefix);

echo "3. Exporting Catalog Categories...\n";
$catController = new \App\Controllers\CatalogController();
$categories = Snapshot::loadArray('categories.php', []);

foreach ($categories as $cat) {
    $slug = $cat['slug'];
    $catResp = $catController->category(new Request('GET', "/catalog/{$slug}"), ['cat' => $slug]);
    savePage($exportDir . "/catalog/{$slug}", 'index.html', $catResp->getContent(), $basePrefix);
    echo "   - Category: {$slug}\n";
}

echo "4. Collecting and Exporting Products (Product Detail Pages)...\n";
$prodController = new \App\Controllers\ProductController();
$productIdsToExport = [];

// A. Home page products (Drops, Popular, Newest, Featured)
$homeData = Snapshot::loadArray('home.php', []);
foreach (['drops', 'popular', 'newest'] as $key) {
    foreach ($homeData[$key] ?? [] as $item) {
        if (!empty($item['id'])) {
            $productIdsToExport[(int)$item['id']] = true;
        }
    }
}
if (!empty($homeData['featured_drop']['id'])) {
    $productIdsToExport[(int)$homeData['featured_drop']['id']] = true;
}

// B. Category items (first 36 items per category)
foreach ($categories as $cat) {
    $catId = $cat['id'];
    $catRows = Snapshot::loadArray("cat/{$catId}.php", []);
    foreach (array_slice($catRows, 0, 36) as $row) {
        if (!empty($row['id'])) {
            $productIdsToExport[(int)$row['id']] = true;
        }
    }
    // Also include top ordered products
    $orderFile = "cat/{$catId}.order.php";
    $orderData = Snapshot::loadArray($orderFile, []);
    $orderedIds = $orderData['popular'] ?? $orderData['price_asc'] ?? [];
    foreach (array_slice($orderedIds, 0, 20) as $pid) {
        $productIdsToExport[(int)$pid] = true;
    }
}

// C. Expand with neighbor products and similar models
$expandedIds = $productIdsToExport;
foreach (array_keys($productIdsToExport) as $pid) {
    $p = Pack::get((int)$pid);
    if (!$p) continue;
    $cRows = Snapshot::loadArray("cat/{$p['cat']}.php", []);
    $cIdx = -1;
    foreach ($cRows as $idx => $r) {
        if ($r['id'] === (int)$pid) { $cIdx = $idx; break; }
    }
    if ($cIdx > 0 && isset($cRows[$cIdx - 1]['id'])) {
        $expandedIds[(int)$cRows[$cIdx - 1]['id']] = true;
    }
    if ($cIdx >= 0 && isset($cRows[$cIdx + 1]['id'])) {
        $expandedIds[(int)$cRows[$cIdx + 1]['id']] = true;
    }
    $simCount = 0;
    foreach ($cRows as $r) {
        if ($r['id'] !== (int)$pid) {
            $expandedIds[(int)$r['id']] = true;
            $simCount++;
            if ($simCount >= 4) break;
        }
    }
}

$productIdsToExport = array_keys($expandedIds);
$exportedProductsCount = 0;
$searchIndex = [];
$suggestIndex = [];

foreach ($productIdsToExport as $pid) {
    $p = Pack::get((int)$pid);
    if (!$p) continue;

    $slug = $p['slug'] ?? ('product-' . $pid);
    $prodResp = $prodController->show(new Request('GET', "/p/{$slug}-{$pid}"), ['slug' => $slug, 'id' => (string)$pid]);
    savePage($exportDir . "/p/{$slug}-{$pid}", 'index.html', $prodResp->getContent(), $basePrefix);
    $exportedProductsCount++;

    $catName = $categories[$p['cat']]['name'] ?? 'Каталог';
    $searchIndex[] = [
        'id' => $pid,
        'title' => $p['title'],
        'brand' => $p['brand'],
        'cat' => $catName,
        'price' => $p['agg']['min'] ?? 0,
        'offers' => $p['agg']['cnt'] ?? 0,
        'url' => "{$basePrefix}/p/{$slug}-{$pid}/",
        'image' => "{$basePrefix}" . ($p['img'] ?? "/assets/img/p/" . ($p['cat'] ?? 10) . ".svg"),
        'specs' => $p['specs'] ?? [],
        'attrs' => $p['attrs'] ?? []
    ];

    $suggestIndex[] = [
        'title' => $p['title'],
        'brand' => $p['brand'],
        'price' => $p['agg']['min'] ?? 0,
        'url' => "{$basePrefix}/p/{$slug}-{$pid}/"
    ];
}
echo "   - Total product pages exported: {$exportedProductsCount}\n";

echo "5. Building Client-Side Search Engine for GitHub Pages...\n";
@mkdir($exportDir . '/api', 0775, true);
file_put_contents($exportDir . '/api/search_index.json', json_encode($searchIndex, JSON_UNESCAPED_UNICODE));
file_put_contents($exportDir . '/api/suggest.json', json_encode(array_slice($suggestIndex, 0, 100), JSON_UNESCAPED_UNICODE));

$searchController = new \App\Controllers\SearchController();
$searchResp = $searchController->index(new Request('GET', '/search', ['q' => '']));
savePage($exportDir . '/search', 'index.html', $searchResp->getContent(), $basePrefix);

echo "6. Copying Assets, Icons and Images...\n";
exec("cp -r " . escapeshellarg($root . '/public/assets') . " " . escapeshellarg($exportDir . '/assets'));
@copy($root . '/public/robots.txt', $exportDir . '/robots.txt');
@copy($root . '/public/sitemap.xml', $exportDir . '/sitemap.xml');
touch($exportDir . '/.nojekyll');

echo "=== Export Complete! Static Site ready in: {$exportDir} ===\n";
