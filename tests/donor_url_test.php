<?php
declare(strict_types=1);

/**
 * Comprehensive Test Suite for DonorUrlHelper & Donor URLs Generation
 * Verifies all 17 electronics categories:
 * Processors, Motherboards, RAM, SSD, Video Cards, PSUs, Cases, Coolers,
 * Laptops, Monitors, Prebuilt PCs, Smartphones, Tablets, Smartwatches,
 * Headphones, TVs, and Robot Vacuums.
 *
 * Also verifies URL generation across all 11 donor stores:
 * Wildberries, Ozon, DNS, Citilink, M.Video, Regard, OnlineTrade,
 * Yandex Market, Megamarket, AliExpress, and Joom.
 */

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
\App\Core\Config::init($root . '/config');

use App\Services\DonorUrlHelper;
use App\Core\Config;

$totalAssertions = 0;
$failedAssertions = 0;

function assertCondition(string $name, bool $condition, string $msg = ''): void
{
    global $totalAssertions, $failedAssertions;
    $totalAssertions++;
    if ($condition) {
        echo "  [PASS] {$name}\n";
    } else {
        $failedAssertions++;
        echo "  [FAIL] {$name}: {$msg}\n";
    }
}

echo "=== Running DonorUrlHelper & External Store URL Tests ===\n\n";

// SECTION 1: All 17 Electronics Categories Full Coverage
echo "-- 1. All 17 Electronics Categories Clean Model Query Tests --\n";
$categorySamples = [
    // 10: Процессоры (CPUs)
    ['AMD', 'Процессор AMD Ryzen 7 7800X3D OEM (BOX, White)', 'AMD Ryzen 7 7800X3D', 'CPUs'],
    ['Intel', 'Процессор Intel Core i5-12400F OEM (OEM, Dark Blue)', 'Intel Core i5-12400F', 'CPUs'],
    // 11: Материнские платы (Motherboards)
    ['ASUS', 'Материнская плата ASUS TUF GAMING B650-PLUS (Titanium)', 'ASUS TUF GAMING B650-PLUS', 'Motherboards'],
    ['MSI', 'Материнская плата MSI MAG B760 TOMAHAWK WIFI', 'MSI MAG B760 TOMAHAWK WIFI', 'Motherboards'],
    // 12: Оперативная память (RAM)
    ['Kingston', 'Оперативная память Kingston FURY Beast DDR5 32GB (2x16GB) 6000MHz', 'Kingston FURY Beast DDR5 32GB', 'RAM'],
    // 13: SSD и накопители (SSDs)
    ['Samsung', 'SSD накопитель Samsung 990 PRO 2TB NVMe M.2', 'Samsung 990 PRO 2TB', 'SSD'],
    // 14: Видеокарты (GPUs) - Note: Must preserve Super, Ti, XT
    ['Palit', 'Видеокарта Palit GeForce RTX 4090 GameRock 24GB', 'Palit GeForce RTX 4090 GameRock', 'GPUs'],
    ['ASUS', 'Видеокарта ASUS ROG Strix GeForce RTX 4080 Super 16GB', 'ASUS ROG Strix GeForce RTX 4080 Super', 'GPUs'],
    ['MSI', 'Видеокарта MSI GeForce RTX 4060 Ventus 2X Black 8G OC', 'MSI GeForce RTX 4060 Ventus 2X', 'GPUs'],
    ['Sapphire', 'Видеокарта Sapphire Pure Radeon RX 7800 XT 16GB White', 'Sapphire Pure Radeon RX 7800 XT', 'GPUs'],
    // 15: Блоки питания (PSUs)
    ['Corsair', 'Блок питания Corsair RM850x 850W Gold (Dark Blue)', 'Corsair RM850x 850W Gold', 'PSUs'],
    ['Cougar', 'Блок питания Cougar GEX850 850W Gold Modular', 'Cougar GEX850 850W Gold', 'PSUs'],
    // 16: Корпуса (PC Cases)
    ['Lian Li', 'Корпус Lian Li O11 Dynamic EVO Black', 'Lian Li O11 Dynamic EVO', 'Cases'],
    ['Montech', 'Корпус Montech AIR 903 MAX Black (4x140mm ARGB Fans)', 'Montech AIR 903 MAX', 'Cases'],
    // 17: Охлаждение ПК (Cooling)
    ['DeepCool', 'Кулер для процессора DeepCool AK620 Digital (TDP 260 Вт)', 'DeepCool AK620 Digital', 'Coolers'],
    ['Noctua', 'Кулер для процессора Noctua NH-D15 chromax.black', 'Noctua NH-D15 chromax.black', 'Coolers'],
    ['Arctic', 'Система водяного охлаждения Arctic Liquid Freezer III 360', 'Arctic Liquid Freezer III 360', 'Coolers'],
    // 20: Ноутбуки (Laptops)
    ['Apple', 'Ноутбук Apple MacBook Air 13 M3 16GB 512GB Midnight', 'Apple MacBook Air 13 M3 16GB 512GB', 'Laptops'],
    ['ASUS', 'Игровой ноутбук ASUS ROG Zephyrus G16 (Core Ultra 9 / RTX 4080 / 32GB / 1TB OLED)', 'ASUS ROG Zephyrus G16', 'Laptops'],
    // 21: Мониторы (Monitors)
    ['LG', 'Монитор LG UltraGear 27GP850-B 27 Nano IPS 165Hz QHD', 'LG UltraGear 27GP850-B', 'Monitors'],
    ['Xiaomi', 'Монитор Xiaomi Mi Curved Gaming Monitor 34 144Hz UWQHD', 'Xiaomi Mi Curved Gaming Monitor 34', 'Monitors'],
    ['Samsung', 'Монитор Samsung Odyssey G5 27 165Hz WQHD Curved 1000R', 'Samsung Odyssey G5', 'Monitors'],
    // 22: Готовые ПК (Prebuilt PCs)
    ['ARDOR GAMING', 'ПК ARDOR GAMING EVO X034 Core i5 / RTX 4060 / 16GB DDR5 / 1TB SSD', 'ARDOR GAMING EVO X034', 'Prebuilt PCs'],
    ['Lenovo', 'ПК Lenovo Legion Tower 5i Core i7 / RTX 4070 / 32GB / 1TB SSD', 'Lenovo Legion Tower 5i', 'Prebuilt PCs'],
    // 30: Смартфоны (Smartphones)
    ['Apple', 'Смартфон Apple iPhone 15 128GB Black', 'Apple iPhone 15 128GB', 'Smartphones'],
    ['Apple', 'Смартфон Apple iPhone 16 Pro Max 256GB Desert Titanium', 'Apple iPhone 16 Pro Max 256GB', 'Smartphones'],
    ['Samsung', 'Смартфон Samsung Galaxy S24 Ultra 256GB Titanium Gray', 'Samsung Galaxy S24 Ultra 256GB', 'Smartphones'],
    ['Xiaomi', 'Смартфон Xiaomi 14 Ultra 512GB Black Leica Camera', 'Xiaomi 14 Ultra 512GB', 'Smartphones'],
    ['OnePlus', 'Смартфон OnePlus 12 16/512GB Silky Black', 'OnePlus 12 16/512GB', 'Smartphones'],
    // 31: Планшеты (Tablets)
    ['Apple', 'Планшет Apple iPad Air 11 M2 128GB Wi-Fi Space Gray', 'Apple iPad Air 11 M2 128GB', 'Tablets'],
    ['Xiaomi', 'Планшет Xiaomi Pad 6 8/256GB Gravity Gray 144Hz', 'Xiaomi Pad 6 8/256GB', 'Tablets'],
    ['Lenovo', 'Планшет Lenovo Tab P12 8/128GB Storm Grey with Pen', 'Lenovo Tab P12 8/128GB', 'Tablets'],
    // 32: Смарт-часы и браслеты (Smartwatches)
    ['Apple', 'Смарт-часы Apple Watch Series 9 GPS 45mm Midnight Aluminum', 'Apple Watch Series 9 45mm', 'Smartwatches'],
    ['Xiaomi', 'Фитнес-браслет Xiaomi Smart Band 8 Pro Black AMOLED', 'Xiaomi Smart Band 8 Pro', 'Smartwatches'],
    // 33: Наушники (Headphones)
    ['Sony', 'Беспроводные наушники Sony WH-1000XM5 Black ANC', 'Sony WH-1000XM5', 'Headphones'],
    ['Apple', 'Беспроводные наушники Apple AirPods Pro 2 (USB-C) White', 'Apple AirPods Pro 2', 'Headphones'],
    ['HyperX', 'Игровая гарнитура HyperX Cloud III Wireless Black/Red', 'HyperX Cloud III Wireless', 'Headphones'],
    // 40: Телевизоры (TVs)
    ['LG', 'Телевизор LG OLED55C3RLA 55 4K 120Hz webOS Smart TV', 'LG OLED55C3RLA', 'TVs'],
    ['Xiaomi', 'Телевизор Xiaomi TV A Pro 55 2025 4K UHD QLED Google TV', 'Xiaomi TV A Pro 55 2025', 'TVs'],
    ['Haier', 'Телевизор Haier 55 Smart TV S3 4K UHD Android TV', 'Haier 55 Smart TV S3', 'TVs'],
    // 50: Роботы-пылесосы (Robot Vacuums)
    ['Roborock', 'Робот-пылесос Roborock S8 Pro Ultra White станция самоочистки', 'Roborock S8 Pro Ultra', 'Robot Vacuums'],
    ['Redmond', 'Робот-пылесос Redmond RV-R650S WiFi влажная уборка', 'Redmond RV-R650S', 'Robot Vacuums']
];

foreach ($categorySamples as [$brand, $input, $expected, $catLabel]) {
    $clean = DonorUrlHelper::cleanModelQuery($input, $brand);
    assertCondition("[{$catLabel}] Clean: {$expected}", $clean === $expected, "Expected '{$expected}', got '{$clean}'");
}

// SECTION 2: Long Titles Edge Cases
echo "\n-- 2. Long Titles Edge Cases --\n";
$longCases = [
    [
        'Apple',
        'Смартфон Apple iPhone 15 Pro Max 256GB Dual SIM Natural Titanium (A3106) with Super Retina XDR Display and Action Button',
        'Apple iPhone 15 Pro Max 256GB Dual SIM Natural Titanium with Super Retina XDR Display and Action Button'
    ],
    [
        'Lenovo',
        'Ноутбук Lenovo Legion Pro 5 16IRX8 16 WQXGA 240Hz Intel Core i7-13700HX / 16GB / 1TB SSD / RTX 4070 8GB / No OS Onyx Grey (82WK008DRK)',
        'Lenovo Legion Pro 5 16IRX8 16 WQXGA 240Hz'
    ],
    [
        'ASUS',
        'Игровой ПК ASUS ROG Strix GT15 G15CF-71270F058W Core i7-12700F / RTX 3070 8GB / 32GB DDR4 / 1TB SSD / Windows 11 Home Star Black',
        'ASUS ROG Strix GT15 G15CF-71270F058W'
    ],
    [
        'Xiaomi',
        'Смартфон Xiaomi 14 Ultra 16/512GB Leica Summilux Quad Camera 50MP Snapdragon 8 Gen 3 90W HyperCharge Black',
        'Xiaomi 14 Ultra 16/512GB Leica Summilux Quad Camera 50MP Snapdragon 8 Gen 3 90W HyperCharge'
    ]
];

foreach ($longCases as [$brand, $input, $expected]) {
    $clean = DonorUrlHelper::cleanModelQuery($input, $brand);
    assertCondition("Long title cleaning: " . substr($expected, 0, 40) . '...', $clean === $expected, "Expected '{$expected}', got '{$clean}'");
    assertCondition("No double space in long title", !str_contains($clean, '  '), "Double space detected in '{$clean}'");
}

// SECTION 3: Specifications with Slashes & Fractions
echo "\n-- 3. Specifications with Slashes & Fractions --\n";
$slashCases = [
    ['Xiaomi', 'Смартфон Xiaomi Redmi Note 13 8/128GB Midnight Black', 'Xiaomi Redmi Note 13 8/128GB'],
    ['realme', 'Смартфон realme 12 Pro+ 5G 12/256GB Submarine Blue', 'realme 12 Pro+ 5G 12/256GB Submarine Blue'],
    ['OnePlus', 'Смартфон OnePlus 12 16/512GB Silky Black', 'OnePlus 12 16/512GB'],
    ['HONOR', 'Планшет HONOR Pad 9 8/128GB Space Gray with Keyboard', 'HONOR Pad 9 8/128GB'],
    ['Apple', 'Планшет Apple iPad Air 11 M2 128GB Wi-Fi Space Gray', 'Apple iPad Air 11 M2 128GB'],
    ['MSI', 'Готовый ПК MSI MAG Codex 5 Intel Core i5 / RTX 4060 / 16GB / 512GB', 'MSI MAG Codex 5'],
    ['HyperX', 'Наушники HyperX Cloud II Black/Red', 'HyperX Cloud II']
];

foreach ($slashCases as [$brand, $input, $expected]) {
    $clean = DonorUrlHelper::cleanModelQuery($input, $brand);
    assertCondition("Slash/Fraction spec: '{$expected}'", $clean === $expected, "Expected '{$expected}', got '{$clean}'");
    assertCondition("No trailing slash in '{$clean}'", !str_ends_with($clean, '/'), "Trailing slash found in '{$clean}'");
    assertCondition("No double slash in '{$clean}'", !str_contains($clean, '//'), "Double slash found in '{$clean}'");
}

// SECTION 4: Brand Presence, Absence, and Aliasing
echo "\n-- 4. Brand Presence, Absence, and Aliasing --\n";
$brandCases = [
    ['Samsung', 'Galaxy S24 Ultra 256GB Titanium Gray', 'Samsung Galaxy S24 Ultra 256GB'],
    ['Google', 'Pixel 8 Pro 128GB Obsidian', 'Google Pixel 8 Pro 128GB'],
    ['Samsung', 'Смартфон Samsung Galaxy S24 Ultra 256GB Titanium Gray', 'Samsung Galaxy S24 Ultra 256GB'],
    ['Apple', 'Смартфон Apple iPhone 15 128GB Black', 'Apple iPhone 15 128GB'],
    ['Western Digital', 'SSD накопитель WD Black SN850X 1TB NVMe M.2', 'WD Black SN850X 1TB'],
    ['Western Digital', 'WD Blue SA510 500GB', 'WD Blue SA510 500GB'],
    ['Western Digital', 'Western Digital Blue 1TB', 'Western Digital Blue 1TB'],
    ['', 'Видеокарта Gigabyte GeForce RTX 4070 GAMING OC 12G', 'Gigabyte GeForce RTX 4070 GAMING OC 12G'],
    ['', 'Ноутбук Apple MacBook Pro 14 M3', 'Apple MacBook Pro 14 M3']
];

foreach ($brandCases as [$brand, $input, $expected]) {
    $clean = DonorUrlHelper::cleanModelQuery($input, $brand);
    assertCondition("Brand handling: '{$expected}'", $clean === $expected, "Expected '{$expected}', got '{$clean}'");
}

// Explicit model argument overrides title
$modelClean = DonorUrlHelper::cleanModelQuery('Смартфон Apple iPhone 15 128GB Black', 'Apple', 'iPhone 15');
assertCondition("Explicit model overrides title: Apple iPhone 15", $modelClean === 'Apple iPhone 15', "Got '{$modelClean}'");

// SECTION 5: External Store URLs Generation for All 11 Donor Stores
echo "\n-- 5. External Store URLs Generation (All 11 Donor Stores) --\n";
$shops = Config::get('shops', []);

$expectedStores = [
    'wildberries', 'ozon', 'dns', 'citilink', 'mvideo',
    'regard', 'onlinetrade', 'yandex_market', 'megamarket',
    'aliexpress', 'joom'
];

foreach ($expectedStores as $shopId) {
    assertCondition("Store '{$shopId}' configured in config/shops.php", isset($shops[$shopId]), "Shop {$shopId} missing from config");
    assertCondition("Store '{$shopId}' has search_url_template", !empty($shops[$shopId]['search_url_template']), "Template missing");
}

// Special canonical verification for Wildberries
$wbTemplate = $shops['wildberries']['search_url_template'] ?? '';
assertCondition(
    "Wildberries has canonical search template with page=1&sort=popular&search={q}",
    str_contains($wbTemplate, 'catalog/0/search.aspx') && str_contains($wbTemplate, 'page=1&sort=popular&search={q}'),
    "Got: {$wbTemplate}"
);

// Verify store URLs generated for sample queries
$storeTestQueries = [
    'Apple iPhone 15 128GB',
    'AMD Ryzen 7 7800X3D',
    'Xiaomi Redmi Note 13 8/128GB',
    'Palit GeForce RTX 4090 GameRock',
    'Samsung 990 PRO 2TB',
    'Sony WH-1000XM5',
    'realme 12 Pro+ 5G 12/256GB'
];

foreach ($storeTestQueries as $query) {
    $allStoreUrls = DonorUrlHelper::buildAllStoreUrls($query, $shops);
    assertCondition("buildAllStoreUrls returned URLs for query: {$query}", count($allStoreUrls) >= 11, "Expected >=11 URLs");

    foreach ($expectedStores as $shopId) {
        $url = $allStoreUrls[$shopId] ?? '';
        assertCondition("URL generated for {$shopId} ({$query})", !empty($url), "URL was empty");
        assertCondition("No double spaces in {$shopId} URL", !str_contains($url, '%20%20') && !str_contains($url, '  '), "Double space: {$url}");

        // RFC 3986 check: spaces MUST be %20, not +
        // Plus in model names (e.g. Pro+) must be %2B
        if (str_contains($query, ' ')) {
            assertCondition("RFC 3986 %20 used for spaces in {$shopId}", str_contains($url, '%20'), "Expected %20 in {$url}");
        }

        // Wildberries specific check
        if ($shopId === 'wildberries') {
            assertCondition(
                "Wildberries URL contains canonical parameters page=1&sort=popular&search=",
                str_contains($url, 'catalog/0/search.aspx?page=1&sort=popular&search=' . rawurlencode($query)),
                "WB URL mismatch: {$url}"
            );
        }

        // Joom specific check: {q} is in path segment, spaces MUST be %20
        if ($shopId === 'joom') {
            assertCondition(
                "Joom URL contains percent-encoded query in path",
                str_contains($url, '/ru/search/' . rawurlencode($query)),
                "Joom URL mismatch: {$url}"
            );
        }

        // General validity checks
        $parsed = parse_url($url);
        assertCondition("Valid HTTPS scheme for {$shopId}", ($parsed['scheme'] ?? '') === 'https', "Non-HTTPS in {$url}");
        assertCondition("Valid host for {$shopId}", !empty($parsed['host']), "Missing host in {$url}");
        assertCondition("No garbage characters in {$shopId} URL", !preg_match('/[<>{}\"|^`]/', $url), "Garbage chars in {$url}");
    }
}

echo "\n========================================================\n";
if ($failedAssertions === 0) {
    echo "  ALL DONOR URL TESTS PASSED! ({$totalAssertions} assertions passed, 0 failures)\n";
    echo "========================================================\n";
    exit(0);
} else {
    echo "  DONOR URL TESTS FAILED! ({$totalAssertions} assertions, {$failedAssertions} failures)\n";
    echo "========================================================\n";
    exit(1);
}
