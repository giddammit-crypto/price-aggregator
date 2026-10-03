<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');

use App\Services\StaticProductLinks as Links;

function assertLinks(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException($description);
}

$fixture = sys_get_temp_dir() . '/pricehub-static-links-' . bin2hex(random_bytes(6));
mkdir($fixture . '/p/real-1', 0775, true);
mkdir($fixture . '/api', 0775, true);

try {
    $validUrl = '/price-aggregator/p/real-1/';
    $missingUrl = '/price-aggregator/p/missing-2/';
    file_put_contents($fixture . '/p/real-1/index.html', '<script>{"@type": "Product", "url": "https://example.test/p/real-1"}</script>');
    file_put_contents($fixture . '/api/search_index.json', json_encode([['id' => 1, 'url' => $validUrl]]));
    file_put_contents($fixture . '/api/suggest.json', json_encode([['url' => $validUrl]]));
    $html = '<article class="product-card" data-product-id="1"><a href="' . $validUrl . '">Real</a></article>'
        . '<article class="product-card" data-product-id="2"><a href="' . $missingUrl . '">Missing</a></article>'
        . '<nav><a href="' . $missingUrl . '"><span>Next</span></a></nav>';
    file_put_contents($fixture . '/index.html', $html);

    $broken = Links::check($fixture);
    assertLinks(count($broken['errors']) === 2, 'fixture should detect both missing product links');
    $filtered = Links::prune($html, ['/p/real-1/' => true]);
    assertLinks(str_contains($filtered, 'Real') && !str_contains($filtered, 'Missing') && !str_contains($filtered, 'Next')
        && !str_contains($filtered, 'data-product-id="2"'), 'remove whole missing card and non-card navigation');
    file_put_contents($fixture . '/index.html', $filtered);
    assertLinks(Links::check($fixture)['errors'] === [], 'filtered artifact must pass all product link checks');

    file_put_contents($fixture . '/p/real-1/index.html', '<h1>Товар не найден</h1>');
    assertLinks(count(Links::check($fixture)['errors']) === 1, 'do not accept a fake 200 product page');
    echo "[PASS] Exported product link closure, card removal, concrete product page checks\n";
} finally {
    @unlink($fixture . '/p/real-1/index.html');
    @unlink($fixture . '/api/search_index.json');
    @unlink($fixture . '/api/suggest.json');
    @unlink($fixture . '/index.html');
    @rmdir($fixture . '/p/real-1');
    @rmdir($fixture . '/p');
    @rmdir($fixture . '/api');
    @rmdir($fixture);
}
