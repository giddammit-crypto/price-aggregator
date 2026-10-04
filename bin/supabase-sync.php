<?php
/**
 * Supabase Synchronization & SQL Seed Generator
 * Exports catalog data to Supabase (via PostgREST batch upsert or SQL seed files)
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Storage\Pack;
use App\Services\SupabaseClient;

echo "=== Supabase Synchronization & Seed Generator ===\n";

$categories = require $root . '/config/categories.php';
$shops = [
    ['id' => 'citilink', 'name' => 'Ситилинк', 'kind' => 'retail', 'mode' => 'prices', 'url_template' => 'https://www.citilink.ru/search/?text={q}', 'color' => '#FF5000'],
    ['id' => 'regard', 'name' => 'Регард', 'kind' => 'retail', 'mode' => 'prices', 'url_template' => 'https://www.regard.ru/catalog?search={q}', 'color' => '#0055A5'],
    ['id' => 'onlinetrade', 'name' => 'ОнлайнТрейд', 'kind' => 'retail', 'mode' => 'prices', 'url_template' => 'https://www.onlinetrade.ru/sitesearch.html?query={q}', 'color' => '#1D70B8'],
    ['id' => 'mvideo', 'name' => 'М.Видео', 'kind' => 'retail', 'mode' => 'prices', 'url_template' => 'https://www.mvideo.ru/listing?q={q}', 'color' => '#E30613'],
    ['id' => 'ozon', 'name' => 'Ozon', 'kind' => 'marketplace', 'mode' => 'prices', 'url_template' => 'https://www.ozon.ru/search/?text={q}', 'color' => '#005BFF'],
    ['id' => 'wildberries', 'name' => 'Wildberries', 'kind' => 'marketplace', 'mode' => 'link_only', 'url_template' => 'https://www.wildberries.ru/catalog/0/search.aspx?page=1&sort=popular&search={q}', 'color' => '#CB11AB'],
    ['id' => 'megamarket', 'name' => 'Мегамаркет', 'kind' => 'marketplace', 'mode' => 'prices', 'url_template' => 'https://megamarket.ru/catalog/?q={q}', 'color' => '#270560'],
    ['id' => 'yandex_market', 'name' => 'Яндекс Маркет', 'kind' => 'marketplace', 'mode' => 'prices', 'url_template' => 'https://market.yandex.ru/search?text={q}', 'color' => '#FC3F1D'],
    ['id' => 'aliexpress', 'name' => 'AliExpress', 'kind' => 'crossborder', 'mode' => 'prices', 'url_template' => 'https://aliexpress.ru/wholesale?SearchText={q}', 'color' => '#FF4747'],
    ['id' => 'dns', 'name' => 'DNS', 'kind' => 'retail', 'mode' => 'link_only', 'url_template' => 'https://www.dns-shop.ru/search/?q={q}', 'color' => '#ED6C00'],
    ['id' => 'avito', 'name' => 'Авито', 'kind' => 'classifieds', 'mode' => 'link_only', 'url_template' => 'https://www.avito.ru/rossiya?q={q}', 'color' => '#00AAFF']
];

$seedSqlFile = $root . '/supabase/seed.sql';
$fp = fopen($seedSqlFile, 'wb');

fwrite($fp, "-- Supabase Seed Data for PriceHub Aggregator\n");
fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

// 1. Seed Categories
fwrite($fp, "-- 1. Categories\n");
foreach ($categories as $c) {
    $parentVal = $c['parent_id'] !== null ? (int)$c['parent_id'] : 'NULL';
    $facetsJson = addslashes(json_encode($c['facets'] ?? [], JSON_UNESCAPED_UNICODE));
    $name = addslashes($c['name']);
    $slug = addslashes($c['slug']);
    $icon = addslashes($c['icon'] ?? 'box');
    fwrite($fp, "INSERT INTO public.categories (id, parent_id, name, slug, icon, facets) VALUES ({$c['id']}, {$parentVal}, '{$name}', '{$slug}', '{$icon}', '{$facetsJson}'::jsonb) ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, facets = EXCLUDED.facets;\n");
}
fwrite($fp, "\n");

// 2. Seed Shops
fwrite($fp, "-- 2. Shops\n");
foreach ($shops as $s) {
    $name = addslashes($s['name']);
    $tmpl = addslashes($s['url_template']);
    fwrite($fp, "INSERT INTO public.shops (id, name, kind, mode, url_template, color) VALUES ('{$s['id']}', '{$name}', '{$s['kind']}', '{$s['mode']}', '{$tmpl}', '{$s['color']}') ON CONFLICT (id) DO NOTHING;\n");
}
fwrite($fp, "\n");

fclose($fp);
echo "[OK] Generated base categories and shops in {$seedSqlFile}\n";

$client = new SupabaseClient();
if ($client->isConfigured()) {
    echo "Supabase credentials detected! Syncing categories to remote Supabase database...\n";
    $catRows = [];
    foreach ($categories as $c) {
        $catRows[] = [
            'id' => $c['id'],
            'parent_id' => $c['parent_id'],
            'name' => $c['name'],
            'slug' => $c['slug'],
            'icon' => $c['icon'] ?? 'box',
            'facets' => $c['facets'] ?? []
        ];
    }
    $res = $client->upsert('categories', $catRows, 'id');
    echo $res ? "[OK] Categories successfully synced to Supabase!\n" : "[WARN] Categories sync failed (check permissions/RLS)\n";

    $shopRes = $client->upsert('shops', $shops, 'id');
    echo $shopRes ? "[OK] Shops successfully synced to Supabase!\n" : "[WARN] Shops sync failed\n";
} else {
    echo "[INFO] Remote Supabase key not set in environment yet. Seed SQL written to supabase/seed.sql\n";
}
