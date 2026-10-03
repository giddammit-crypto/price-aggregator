<?php
/**
 * Data Seeder: generates 20,000 products & 100,000 offers with low-memory streaming batches
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Storage\Pack;
use App\Storage\Fs;
use App\Storage\Repositories\FileHistoryRepository;
use App\Services\BuildIndexService;
use App\Core\Utf8;

$targetProducts = 20000;
echo "=== Starting Streaming Data Seeder for {$targetProducts} Products & 100,000 Offers ===\n";

$startTime = microtime(true);

$catalogDir = $root . '/data/catalog/products';
$itemsDir = $root . '/data/items';
$historyDir = $root . '/data/history';

Pack::init($catalogDir);
$historyRepo = new FileHistoryRepository($historyDir);

$categories = [
    10 => [
        'name' => 'Процессор',
        'brands' => ['Intel', 'AMD'],
        'models' => [
            ['Core i3-14100', 'LGA1700', 4, 8, 60, 11500],
            ['Core i5-14400F', 'LGA1700', 10, 16, 65, 18900],
            ['Core i5-14600K', 'LGA1700', 14, 20, 125, 29500],
            ['Core i7-14700K', 'LGA1700', 20, 28, 125, 39900],
            ['Core i9-14900K', 'LGA1700', 24, 32, 125, 54900],
            ['Ryzen 5 5600', 'AM4', 6, 12, 65, 10500],
            ['Ryzen 5 7600X', 'AM5', 6, 12, 105, 19900],
            ['Ryzen 7 7700X', 'AM5', 8, 16, 105, 28900],
            ['Ryzen 7 7800X3D', 'AM5', 8, 16, 120, 42900],
            ['Ryzen 9 7950X', 'AM5', 16, 32, 170, 52900]
        ]
    ],
    14 => [
        'name' => 'Видеокарта',
        'brands' => ['ASUS', 'MSI', 'Gigabyte', 'Palit', 'Sapphire', 'Inno3D'],
        'models' => [
            ['GeForce RTX 4060 Dual', 'RTX 4060', 8, 31900],
            ['GeForce RTX 4060 Ti Gaming', 'RTX 4060 Ti', 16, 46900],
            ['GeForce RTX 4070 Dual OC', 'RTX 4070', 12, 57900],
            ['GeForce RTX 4070 SUPER Gaming OC', 'RTX 4070 SUPER', 12, 65900],
            ['GeForce RTX 4070 Ti SUPER Gaming', 'RTX 4070 Ti SUPER', 16, 84900],
            ['GeForce RTX 4080 SUPER Suprim', 'RTX 4080 SUPER', 16, 114900],
            ['GeForce RTX 4090 Gaming OC', 'RTX 4090', 24, 189900],
            ['Radeon RX 7600 Pulse', 'RX 7600', 8, 27900],
            ['Radeon RX 7800 XT Nitro+', 'RX 7800 XT', 16, 54900],
            ['Radeon RX 7900 XTX Gaming', 'RX 7900 XTX', 24, 98900]
        ]
    ],
    13 => [
        'name' => 'SSD накопитель',
        'brands' => ['Samsung', 'Kingston', 'Crucial', 'Western Digital', 'ADATA'],
        'models' => [
            ['980 PRO M.2 NVMe', 'M.2 2280', 1000, 9900],
            ['990 PRO Heatsink NVMe', 'M.2 2280', 2000, 18900],
            ['KC3000 PCIe 4.0 NVMe', 'M.2 2280', 1024, 8900],
            ['KC3000 PCIe 4.0 NVMe 2TB', 'M.2 2280', 2048, 15900],
            ['P3 Plus Gen4 NVMe', 'M.2 2280', 500, 4900],
            ['Black SN850X Gaming', 'M.2 2280', 1000, 10900],
            ['Legend 960 MAX', 'M.2 2280', 2000, 16400]
        ]
    ],
    12 => [
        'name' => 'Оперативная память',
        'brands' => ['Kingston', 'Corsair', 'G.Skill', 'ADATA', 'Team Group'],
        'models' => [
            ['Fury Beast Black 32GB (2x16GB)', 'DDR5', 32, 6000, 12900],
            ['Fury Renegade RGB 32GB (2x16GB)', 'DDR5', 32, 6400, 14900],
            ['Vengeance RGB 64GB (2x32GB)', 'DDR5', 64, 6000, 23900],
            ['Trident Z5 Neo RGB 32GB (2x16GB)', 'DDR5', 32, 6000, 15500],
            ['Fury Beast 16GB (2x8GB)', 'DDR4', 16, 3200, 4200]
        ]
    ],
    20 => [
        'name' => 'Ноутбук',
        'brands' => ['Apple', 'ASUS', 'Lenovo', 'Xiaomi', 'Acer', 'MSI'],
        'models' => [
            ['MacBook Air 13 M3 16/512GB', 13.6, 'Apple M3', 16, 512, 134900],
            ['MacBook Pro 14 M3 Pro 18/512GB', 14.2, 'Apple M3 Pro', 18, 512, 199900],
            ['ROG Zephyrus G16 RTX 4070', 16.0, 'Core Ultra 7', 32, 1000, 219900],
            ['Legion Pro 5 16IRX9 RTX 4060', 16.0, 'Core i7-14650HX', 16, 1000, 149900],
            ['RedmiBook Pro 16 2024 Ultra 7', 16.0, 'Core Ultra 7', 32, 1000, 94900]
        ]
    ],
    30 => [
        'name' => 'Смартфон',
        'brands' => ['Apple', 'Samsung', 'Xiaomi', 'Google', 'Realme', 'HONOR'],
        'models' => [
            ['iPhone 15 128GB', 128, 6, 69900],
            ['iPhone 15 Pro 128GB', 128, 8, 97900],
            ['iPhone 15 Pro Max 256GB', 256, 8, 119900],
            ['Galaxy S24 256GB', 256, 8, 67900],
            ['Galaxy S24 Ultra 512GB', 512, 12, 114900],
            ['14 12/512GB', 512, 12, 74900],
            ['Redmi Note 13 Pro+ 5G 8/256GB', 256, 8, 31900],
            ['Pixel 8 128GB', 128, 8, 54900],
            ['Pixel 8 Pro 256GB', 256, 12, 79900],
            ['Magic 6 Pro 12/512GB', 512, 12, 89900]
        ]
    ],
    33 => [
        'name' => 'Наушники',
        'brands' => ['Sony', 'Apple', 'Sennheiser', 'Marshall', 'JBL'],
        'models' => [
            ['WH-1000XM5 Wireless ANC', 'full-size', true, true, 31900],
            ['AirPods Pro 2 USB-C', 'tws', true, true, 21900],
            ['AirPods Max', 'full-size', true, true, 54900],
            ['Major IV Black', 'on-ear', true, false, 11900],
            ['Momentum 4 Wireless', 'full-size', true, true, 28900]
        ]
    ],
    40 => [
        'name' => 'Телевизор',
        'brands' => ['LG', 'Samsung', 'TCL', 'Xiaomi', 'Hisense'],
        'models' => [
            ['OLED55C3 4K 120Hz', 55, '4K UHD', true, 129900],
            ['QE65Q70C QLED 4K', 65, '4K UHD', true, 89900],
            ['65C745 QLED 144Hz', 65, '4K UHD', true, 64900],
            ['TV A Pro 55 4K', 55, '4K UHD', true, 34900]
        ]
    ],
    50 => [
        'name' => 'Робот-пылесос',
        'brands' => ['Roborock', 'Dreame', 'Xiaomi'],
        'models' => [
            ['S8 Pro Ultra со станцией', true, true, 89900],
            ['L10s Ultra Heat', true, true, 69900],
            ['Robot Vacuum X10+', true, true, 44900],
            ['Q7 Max Plus', true, true, 32900]
        ]
    ]
];

$shopsConfig = require $root . '/config/shops.php';
$shopKeys = array_keys($shopsConfig);

// Clean previous catalog shards
$oldFiles = glob($catalogDir . '/p*.*') ?: [];
foreach ($oldFiles as $f) { @unlink($f); }

// Open file handles for 256 product shards to write NDJSON in streaming mode
$shardHandles = [];
$shardIndices = []; // shard => [id => [offset, len]]
$shardOffsets = array_fill(0, 256, 0);

for ($s = 0; $s < 256; $s++) {
    $sName = sprintf('p%03d', $s);
    $filePath = $catalogDir . '/' . $sName . '.ndjson';
    $shardHandles[$s] = fopen($filePath, 'wb');
    $shardIndices[$s] = [];
}

// Open file handles for store items
$storeHandles = [];
foreach ($shopKeys as $sId) {
    @mkdir($itemsDir . '/' . $sId, 0775, true);
    for ($sh = 0; $sh < 16; $sh++) { // 16 shards per shop
        $shName = sprintf('s%02x', $sh * 16);
        $storeHandles[$sId][$sh] = fopen($itemsDir . '/' . $sId . '/' . $shName . '.ndjson', 'wb');
    }
}

$catIds = array_keys($categories);
$catCount = count($catIds);

for ($id = 1; $id <= $targetProducts; $id++) {
    $catId = $catIds[$id % $catCount];
    $catDef = $categories[$catId];
    $models = $catDef['models'];
    $modelDef = $models[$id % count($models)];
    $brand = $catDef['brands'][$id % count($catDef['brands'])];

    $variantNum = (int)ceil($id / count($models));
    $variantSuffix = ($variantNum > 1) ? " (v{$variantNum})" : '';

    $title = "{$catDef['name']} {$brand} {$modelDef[0]}{$variantSuffix}";
    $slug = Utf8::slugify($title);
    $mpn = strtoupper(substr(hash('crc32b', $title), 0, 8)) . '-' . ($id % 900 + 100);
    $ean = sprintf('46%010d%d', $id, ($id % 9));

    $basePrice = $modelDef[count($modelDef) - 1];
    $basePrice += ($id % 11 - 5) * 200;
    if ($basePrice < 1000) $basePrice = 1000;

    $attrs = ['brand' => $brand];
    $specs = ['Бренд' => $brand, 'Артикул производителя' => $mpn, 'Штрихкод' => $ean];

    if ($catId === 14) {
        $attrs['gpu_chip'] = $modelDef[1];
        $attrs['mem_gb'] = $modelDef[2];
        $specs['Видеочипсет'] = $modelDef[1];
        $specs['Объем памяти'] = "{$modelDef[2]} ГБ";
    } elseif ($catId === 10) {
        $attrs['socket'] = $modelDef[1];
        $attrs['cores'] = $modelDef[2];
        $attrs['threads'] = $modelDef[3];
        $specs['Сокет'] = $modelDef[1];
        $specs['Ядер'] = (string)$modelDef[2];
    } elseif ($catId === 13) {
        $attrs['form_factor'] = $modelDef[1];
        $attrs['capacity_gb'] = $modelDef[2];
        $specs['Форм-фактор'] = $modelDef[1];
        $specs['Емкость'] = "{$modelDef[2]} ГБ";
    } elseif ($catId === 30) {
        $attrs['rom_gb'] = $modelDef[1];
        $attrs['ram_gb'] = $modelDef[2];
        $specs['Память'] = "{$modelDef[1]} ГБ";
        $specs['ОЗУ'] = "{$modelDef[2]} ГБ";
    }

    $numOffers = 4 + ($id % 4);
    $offers = [];
    $pricesList = [];
    $hasMp = 0;
    $hasCb = 0;

    for ($o = 0; $o < $numOffers; $o++) {
        $shopId = $shopKeys[($id + $o) % count($shopKeys)];
        $shop = $shopsConfig[$shopId];
        $offerKey = "{$shopId}:{$id}_{$o}";

        $priceDelta = (($id * 7 + $o * 13) % 25 - 10) / 100.0;
        $offerPrice = (int)round($basePrice * (1 + $priceDelta));
        if ($offerPrice < 500) $offerPrice = 500;

        $landed = $offerPrice;
        $origin = 'RU';
        $seller = null;
        $cond = 'new';
        $note = null;

        if ($shop['kind'] === 'marketplace') {
            $hasMp = 1;
            $rating = round(4.3 + (($id + $o) % 7) / 10, 1);
            $isOfficial = ($rating >= 4.8) ? 1 : 0;
            $seller = [
                'id' => "sel_" . ($id % 50 + 1),
                'name' => $isOfficial ? "{$brand} Official Store" : "ТехноМаркет",
                'rating' => $rating,
                'n' => 120 + ($id % 500),
                'official' => $isOfficial
            ];
            if ($shopId === 'ozon') {
                $note = 'с Ozon Картой';
            }
        } elseif ($shop['kind'] === 'crossborder') {
            $hasCb = 1;
            $origin = 'CN';
            $delivDays = 12 + ($id % 10);
            $delivCost = ($offerPrice > 10000) ? 0 : 490;
            $landed = $offerPrice + $delivCost;
            $note = "Из Китая • {$delivDays} дн.";
            $seller = [
                'id' => "cn_sel_" . ($id % 20 + 1),
                'name' => "Global Tech Direct",
                'rating' => 4.7,
                'n' => 2400,
                'official' => 1
            ];
        }

        if ($shop['mode'] === 'link_only') {
            $offers[] = [
                'k' => $offerKey,
                'shop' => $shopId,
                'kind' => $shop['kind'],
                'mode' => 'link_only',
                'price' => null,
                'landed' => null,
                'stock' => 1,
                'cond' => $cond,
                'origin' => $origin,
                'seller' => null,
                'note' => 'Цена на сайте площадки',
                'upd' => '2026-10-03 12:00'
            ];
        } else {
            $offers[] = [
                'k' => $offerKey,
                'shop' => $shopId,
                'kind' => $shop['kind'],
                'mode' => 'prices',
                'price' => $offerPrice,
                'landed' => $landed,
                'stock' => 1,
                'cond' => $cond,
                'origin' => $origin,
                'seller' => $seller,
                'note' => $note,
                'upd' => '2026-10-03 12:00'
            ];
            $pricesList[] = $landed;

            // Stream to store items shard
            $storeShardIdx = ($id % 16);
            $rawLine = json_encode([
                'k' => $offerKey,
                'shop' => $shopId,
                'ext' => (string)$id,
                'url' => $shop['search_url_template'],
                't' => $title,
                'brand' => $brand,
                'mpn' => $mpn,
                'price' => $offerPrice,
                'pid' => $id,
                'seen' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            fwrite($storeHandles[$shopId][$storeShardIdx], $rawLine);
        }
    }

    $minPrice = !empty($pricesList) ? min($pricesList) : $basePrice;
    $maxPrice = !empty($pricesList) ? max($pricesList) : $basePrice;
    $drop = ($id % 7 === 0) ? round(10.0 + ($id % 15), 1) : 0.0;

    $agg = [
        'min' => $minPrice,
        'max' => $maxPrice,
        'cnt' => count($offers),
        'shops' => count(array_unique(array_column($offers, 'shop'))),
        'drop' => $drop,
        'min_landed' => $minPrice,
        'mp' => $hasMp,
        'cb' => $hasCb
    ];

    $pop = ($targetProducts - $id) + ($agg['cnt'] * 20) + (int)($drop * 10);

    $product = [
        'id' => $id,
        'cat' => $catId,
        'brand' => $brand,
        'slug' => $slug,
        'title' => $title,
        'model' => $modelDef[0],
        'mpn' => $mpn,
        'ean' => $ean,
        'specs' => $specs,
        'attrs' => $attrs,
        'img' => "/assets/img/p/{$catId}.svg",
        'pub' => 1,
        'created' => '2026-09-01',
        'updated' => '2026-10-03',
        'popularity' => $pop,
        'offers' => $offers,
        'agg' => $agg
    ];

    // Stream write to product shard
    $shardNum = $id % 256;
    $jsonLine = json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    $lineLen = strlen($jsonLine);
    $offset = $shardOffsets[$shardNum];

    fwrite($shardHandles[$shardNum], $jsonLine);
    $shardIndices[$shardNum][$id] = [$offset, $lineLen];
    $shardOffsets[$shardNum] += $lineLen;

    // History for first 500 items
    if ($id <= 500) {
        $historyRepo->append($id, "citilink:{$id}_0", (float)$minPrice, 1, '2026-10-03');
        $historyRepo->append($id, "citilink:{$id}_0", (float)round($minPrice * 1.08), 1, '2026-09-18');
        $historyRepo->append($id, "citilink:{$id}_0", (float)round($minPrice * 1.12), 1, '2026-09-01');
    }

    if ($id % 5000 === 0) {
        echo "Streamed {$id} / {$targetProducts} products...\n";
    }
}

// Close all product shard handles and write PHP index arrays
echo "Writing index arrays for 256 shards...\n";
for ($s = 0; $s < 256; $s++) {
    fclose($shardHandles[$s]);
    $sName = sprintf('p%03d', $s);
    $idxPath = $catalogDir . '/' . $sName . '.idx.php';
    Fs::atomicWritePhpArray($idxPath, $shardIndices[$s]);
}

// Close store handles
foreach ($shopKeys as $sId) {
    for ($sh = 0; $sh < 16; $sh++) {
        fclose($storeHandles[$sId][$sh]);
    }
}

$elapsed = round(microtime(true) - $startTime, 2);
echo "[OK] Seeding complete: {$targetProducts} products written in {$elapsed}s (Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB).\n";

echo "Building active search and catalog snapshot...\n";
$job = new BuildIndexService($root);
$res = $job->run();
echo "[OK] {$res}\n";
echo "=== Seeder Finished Successfully! ===\n";
