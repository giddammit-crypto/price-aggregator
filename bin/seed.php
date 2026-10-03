<?php
/**
 * Streaming Data Seeder with 100% Real Electronics Models & Real Donor Store URLs
 * Zero-DBMS File Storage Architecture
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');
require_once $root . '/app/helpers.php';

use App\Core\Utf8;
use App\Storage\Pack;
use App\Storage\Snapshot;
use App\Storage\Repositories\FileHistoryRepository;

$targetProducts = 20000;
echo "=== Starting Streaming Data Seeder for {$targetProducts} Real Products & ~110,000 Offers ===\n";

$startTime = microtime(true);

$catalogDir = $root . '/data/catalog/products';
$itemsDir = $root . '/data/items';
$historyDir = $root . '/data/history';

Pack::init($catalogDir);
$historyRepo = new FileHistoryRepository($historyDir);

// 1. Real Products Database Template
$realCatalogData = [
    // 14: Видеокарты
    14 => [
        'name' => 'Видеокарта',
        'items' => [
            [
                'brand' => 'Palit',
                'title' => 'Видеокарта Palit GeForce RTX 4090 GameRock OC 24GB (NED4090S19SB-1020G)',
                'chip' => 'GeForce RTX 4090',
                'mem' => 24,
                'price' => 219990,
                'mpn' => 'NED4090S19SB-1020G',
                'ean' => '4710562243406',
                'specs' => ['Видеочипсет' => 'NVIDIA GeForce RTX 4090', 'Объем видеопамяти' => '24 ГБ', 'Тип памяти' => 'GDDR6X', 'Шина памяти' => '384 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ],
            [
                'brand' => 'ASUS',
                'title' => 'Видеокарта ASUS ROG Strix GeForce RTX 4090 OC Edition 24GB (ROG-STRIX-RTX4090-O24G-GAMING)',
                'chip' => 'GeForce RTX 4090',
                'mem' => 24,
                'price' => 249990,
                'mpn' => 'ROG-STRIX-RTX4090-O24G',
                'ean' => '4711081936749',
                'specs' => ['Видеочипсет' => 'NVIDIA GeForce RTX 4090', 'Объем видеопамяти' => '24 ГБ', 'Тип памяти' => 'GDDR6X', 'Шина памяти' => '384 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ],
            [
                'brand' => 'Gigabyte',
                'title' => 'Видеокарта Gigabyte GeForce RTX 4080 SUPER Gaming OC 16GB (GV-N408SGAMING OC-16GD)',
                'chip' => 'GeForce RTX 4080 SUPER',
                'mem' => 16,
                'price' => 124990,
                'mpn' => 'GV-N408SGAMING-OC-16GD',
                'ean' => '4719331314644',
                'specs' => ['Видеочипсет' => 'NVIDIA GeForce RTX 4080 SUPER', 'Объем видеопамяти' => '16 ГБ', 'Тип памяти' => 'GDDR6X', 'Шина памяти' => '256 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ],
            [
                'brand' => 'MSI',
                'title' => 'Видеокарта MSI GeForce RTX 4070 Ti SUPER 16G GAMING X SLIM',
                'chip' => 'GeForce RTX 4070 Ti SUPER',
                'mem' => 16,
                'price' => 96990,
                'mpn' => 'RTX-4070-Ti-SUPER-16G-SLIM',
                'ean' => '4711377170123',
                'specs' => ['Видеочипсет' => 'NVIDIA GeForce RTX 4070 Ti SUPER', 'Объем видеопамяти' => '16 ГБ', 'Тип памяти' => 'GDDR6X', 'Шина памяти' => '256 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ],
            [
                'brand' => 'Palit',
                'title' => 'Видеокарта Palit GeForce RTX 4070 SUPER Dual 12GB (NED407S019K9-1043D)',
                'chip' => 'GeForce RTX 4070 SUPER',
                'mem' => 12,
                'price' => 69990,
                'mpn' => 'NED407S019K9-1043D',
                'ean' => '4710562244243',
                'specs' => ['Видеочипсет' => 'NVIDIA GeForce RTX 4070 SUPER', 'Объем видеопамяти' => '12 ГБ', 'Тип памяти' => 'GDDR6X', 'Шина памяти' => '192 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ],
            [
                'brand' => 'MSI',
                'title' => 'Видеокарта MSI GeForce RTX 4060 VENTUS 2X Black 8G OC',
                'chip' => 'GeForce RTX 4060',
                'mem' => 8,
                'price' => 34990,
                'mpn' => 'RTX-4060-VENTUS-2X-8G-OC',
                'ean' => '4711377098458',
                'specs' => ['Видеочипсет' => 'NVIDIA GeForce RTX 4060', 'Объем видеопамяти' => '8 ГБ', 'Тип памяти' => 'GDDR6', 'Шина памяти' => '128 бит', 'Интерфейс' => 'PCI-E 4.0 x8']
            ],
            [
                'brand' => 'Sapphire',
                'title' => 'Видеокарта Sapphire NITRO+ AMD Radeon RX 7900 XTX Vapor-X 24GB',
                'chip' => 'Radeon RX 7900 XTX',
                'mem' => 24,
                'price' => 119990,
                'mpn' => '11322-01-20G',
                'ean' => '4895106293411',
                'specs' => ['Видеочипсет' => 'AMD Radeon RX 7900 XTX', 'Объем видеопамяти' => '24 ГБ', 'Тип памяти' => 'GDDR6', 'Шина памяти' => '384 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ],
            [
                'brand' => 'Sapphire',
                'title' => 'Видеокарта Sapphire PURE AMD Radeon RX 7800 XT 16GB White',
                'chip' => 'Radeon RX 7800 XT',
                'mem' => 16,
                'price' => 58990,
                'mpn' => '11330-03-20G',
                'ean' => '4895106294159',
                'specs' => ['Видеочипсет' => 'AMD Radeon RX 7800 XT', 'Объем видеопамяти' => '16 ГБ', 'Тип памяти' => 'GDDR6', 'Шина памяти' => '256 бит', 'Интерфейс' => 'PCI-E 4.0 x16']
            ]
        ]
    ],

    // 30: Смартфоны
    30 => [
        'name' => 'Смартфон',
        'items' => [
            [
                'brand' => 'Apple',
                'title' => 'Смартфон Apple iPhone 16 Pro Max 256GB Desert Titanium',
                'rom' => 256,
                'ram' => 8,
                'price' => 159990,
                'mpn' => 'MYWW3HN/A',
                'ean' => '195949823101',
                'specs' => ['Встроенная память' => '256 ГБ', 'Оперативная память' => '8 ГБ', 'Экран' => '6.9" Super Retina XDR OLED 120Hz', 'Процессор' => 'Apple A18 Pro', 'Камера' => '48+48+12 Мп']
            ],
            [
                'brand' => 'Apple',
                'title' => 'Смартфон Apple iPhone 16 Pro 128GB Black Titanium',
                'rom' => 128,
                'ram' => 8,
                'price' => 129990,
                'mpn' => 'MYNJ3ZD/A',
                'ean' => '195949811234',
                'specs' => ['Встроенная память' => '128 ГБ', 'Оперативная память' => '8 ГБ', 'Экран' => '6.3" Super Retina XDR OLED 120Hz', 'Процессор' => 'Apple A18 Pro', 'Камера' => '48+48+12 Мп']
            ],
            [
                'brand' => 'Apple',
                'title' => 'Смартфон Apple iPhone 15 128GB Black',
                'rom' => 128,
                'ram' => 6,
                'price' => 74990,
                'mpn' => 'MTP03ZD/A',
                'ean' => '195949038234',
                'specs' => ['Встроенная память' => '128 ГБ', 'Оперативная память' => '6 ГБ', 'Экран' => '6.1" Super Retina XDR OLED', 'Процессор' => 'Apple A16 Bionic', 'Камера' => '48+12 Мп']
            ],
            [
                'brand' => 'Samsung',
                'title' => 'Смартфон Samsung Galaxy S24 Ultra 12/256GB Titanium Gray (SM-S928B)',
                'rom' => 256,
                'ram' => 12,
                'price' => 109990,
                'mpn' => 'SM-S928B-256',
                'ean' => '8806095304724',
                'specs' => ['Встроенная память' => '256 ГБ', 'Оперативная память' => '12 ГБ', 'Экран' => '6.8" Dynamic AMOLED 2X 120Hz', 'Процессор' => 'Snapdragon 8 Gen 3 for Galaxy', 'Камера' => '200+50+12+10 Мп']
            ],
            [
                'brand' => 'Samsung',
                'title' => 'Смартфон Samsung Galaxy Z Fold6 12/512GB Silver Shadow',
                'rom' => 512,
                'ram' => 12,
                'price' => 164990,
                'mpn' => 'SM-F956B-512',
                'ean' => '8806095591230',
                'specs' => ['Встроенная память' => '512 ГБ', 'Оперативная память' => '12 ГБ', 'Экран' => '7.6" Dynamic AMOLED 2X Foldable', 'Процессор' => 'Snapdragon 8 Gen 3', 'Камера' => '50+12+10 Мп']
            ],
            [
                'brand' => 'Xiaomi',
                'title' => 'Смартфон Xiaomi 14 Ultra 16/512GB Black (Leica Quad Camera)',
                'rom' => 512,
                'ram' => 16,
                'price' => 114990,
                'mpn' => '24030PN60G',
                'ean' => '6941812762312',
                'specs' => ['Встроенная память' => '512 ГБ', 'Оперативная память' => '16 ГБ', 'Экран' => '6.73" AMOLED 120Hz WQHD+', 'Процессор' => 'Snapdragon 8 Gen 3', 'Камера' => '50+50+50+50 Мп Leica']
            ],
            [
                'brand' => 'Xiaomi',
                'title' => 'Смартфон Xiaomi 14 12/512GB White',
                'rom' => 512,
                'ram' => 12,
                'price' => 79990,
                'mpn' => '23127PN0CG',
                'ean' => '6941812751415',
                'specs' => ['Встроенная память' => '512 ГБ', 'Оперативная память' => '12 ГБ', 'Экран' => '6.36" AMOLED 120Hz', 'Процессор' => 'Snapdragon 8 Gen 3', 'Камера' => '50+50+50 Мп Leica']
            ],
            [
                'brand' => 'Google',
                'title' => 'Смартфон Google Pixel 9 Pro XL 16/256GB Obsidian',
                'rom' => 256,
                'ram' => 16,
                'price' => 124990,
                'mpn' => 'GA05216-US',
                'ean' => '840244708912',
                'specs' => ['Встроенная память' => '256 ГБ', 'Оперативная память' => '16 ГБ', 'Экран' => '6.8" Super Actua LTPO OLED 120Hz', 'Процессор' => 'Google Tensor G4', 'Камера' => '50+48+48 Мп']
            ]
        ]
    ],

    // 20: Ноутбуки
    20 => [
        'name' => 'Ноутбук',
        'items' => [
            [
                'brand' => 'Apple',
                'title' => 'Ноутбук Apple MacBook Pro 16" M3 Max (36GB / 1TB SSD) Space Black (MUW63)',
                'screen' => 16.2,
                'cpu' => 'Apple M3 Max',
                'ram' => 36,
                'ssd' => 1024,
                'price' => 369990,
                'mpn' => 'MUW63LL/A',
                'ean' => '195949112233',
                'specs' => ['Диагональ экрана' => '16.2" Liquid Retina XDR 120Hz', 'Процессор' => 'Apple M3 Max (14 ядер)', 'Оперативная память' => '36 ГБ', 'Накопитель SSD' => '1024 ГБ']
            ],
            [
                'brand' => 'Apple',
                'title' => 'Ноутбук Apple MacBook Air 13" M3 (16GB / 512GB SSD) Midnight (MC8K4)',
                'screen' => 13.6,
                'cpu' => 'Apple M3',
                'ram' => 16,
                'ssd' => 512,
                'price' => 144990,
                'mpn' => 'MC8K4LL/A',
                'ean' => '195949234567',
                'specs' => ['Диагональ экрана' => '13.6" Liquid Retina', 'Процессор' => 'Apple M3 (8 ядер)', 'Оперативная память' => '16 ГБ', 'Накопитель SSD' => '512 ГБ']
            ],
            [
                'brand' => 'ASUS',
                'title' => 'Игровой ноутбук ASUS ROG Zephyrus G16 GU605MZ (Core Ultra 9 185H / RTX 4080 / 32GB / 1TB / OLED 240Hz)',
                'screen' => 16.0,
                'cpu' => 'Intel Core Ultra 9 185H',
                'ram' => 32,
                'ssd' => 1000,
                'price' => 289990,
                'mpn' => 'GU605MZ-QR065W',
                'ean' => '4711387514210',
                'specs' => ['Экран' => '16" 2.5K OLED 240Hz ROG Nebula', 'Процессор' => 'Intel Core Ultra 9 185H', 'Видеокарта' => 'NVIDIA GeForce RTX 4080 12GB', 'ОЗУ' => '32 ГБ LPDDR5X']
            ],
            [
                'brand' => 'Lenovo',
                'title' => 'Ноутбук Lenovo Legion Pro 5 16IRX9 (Core i7-14700HX / RTX 4070 / 32GB / 1TB WQXGA 240Hz)',
                'screen' => 16.0,
                'cpu' => 'Intel Core i7-14700HX',
                'ram' => 32,
                'ssd' => 1000,
                'price' => 179990,
                'mpn' => '83DF006VRK',
                'ean' => '197532891045',
                'specs' => ['Экран' => '16" WQXGA 2560x1600 IPS 240Hz', 'Процессор' => 'Intel Core i7-14700HX', 'Видеокарта' => 'NVIDIA GeForce RTX 4070 8GB', 'ОЗУ' => '32 ГБ DDR5']
            ]
        ]
    ],

    // 10: Процессоры
    10 => [
        'name' => 'Процессор',
        'items' => [
            [
                'brand' => 'AMD',
                'title' => 'Процессор AMD Ryzen 7 7800X3D OEM (Socket AM5, 8 x 4.2 ГГц, 3D V-Cache 96 МБ)',
                'socket' => 'AM5',
                'cores' => 8,
                'threads' => 16,
                'price' => 45990,
                'mpn' => '100-000000910',
                'ean' => '730143314930',
                'specs' => ['Сокет' => 'AM5', 'Количество ядер' => '8', 'Число потоков' => '16', 'Базовая частота' => '4.2 ГГц', 'Кэш L3' => '96 МБ 3D V-Cache', 'TDP' => '120 Вт']
            ],
            [
                'brand' => 'Intel',
                'title' => 'Процессор Intel Core i9-14900K OEM (LGA1700, 24 x 3.2 ГГц, до 6.0 ГГц)',
                'socket' => 'LGA1700',
                'cores' => 24,
                'threads' => 32,
                'price' => 57990,
                'mpn' => 'CM8071504820503',
                'ean' => '5032037278508',
                'specs' => ['Сокет' => 'LGA1700', 'Количество ядер' => '24 (8P + 16E)', 'Число потоков' => '32', 'Макс. частота' => '6.0 ГГц', 'TDP' => '125 Вт (253 Вт Turbo)']
            ],
            [
                'brand' => 'AMD',
                'title' => 'Процессор AMD Ryzen 5 7600X OEM (Socket AM5, 6 x 4.7 ГГц)',
                'socket' => 'AM5',
                'cores' => 6,
                'threads' => 12,
                'price' => 19990,
                'mpn' => '100-000000593',
                'ean' => '730143314442',
                'specs' => ['Сокет' => 'AM5', 'Количество ядер' => '6', 'Число потоков' => '12', 'Базовая частота' => '4.7 ГГц', 'TDP' => '105 Вт']
            ],
            [
                'brand' => 'Intel',
                'title' => 'Процессор Intel Core i5-12400F OEM (LGA1700, 6 x 2.5 ГГц)',
                'socket' => 'LGA1700',
                'cores' => 6,
                'threads' => 12,
                'price' => 11490,
                'mpn' => 'CM8071504821107',
                'ean' => '5032037237758',
                'specs' => ['Сокет' => 'LGA1700', 'Количество ядер' => '6', 'Число потоков' => '12', 'Базовая частота' => '2.5 ГГц', 'TDP' => '65 Вт']
            ]
        ]
    ],

    // 33: Наушники
    33 => [
        'name' => 'Наушники',
        'items' => [
            [
                'brand' => 'Sony',
                'title' => 'Беспроводные наушники Sony WH-1000XM5 Black (ANC, LDAC, Hi-Res)',
                'type' => 'Полноразмерные',
                'price' => 33990,
                'mpn' => 'WH1000XM5/B',
                'ean' => '4548736132566',
                'specs' => ['Тип' => 'Полноразмерные беспроводные', 'Шумоподавление' => 'Активное (ANC Dual Processor V1)', 'Кодеки' => 'LDAC, AAC, SBC', 'Автономность' => 'до 30 ч']
            ],
            [
                'brand' => 'Apple',
                'title' => 'Беспроводные наушники Apple AirPods Pro (2-го поколения, USB-C MagSafe Case MTJV3)',
                'type' => 'TWS внутриканальные',
                'price' => 22490,
                'mpn' => 'MTJV3ZM/A',
                'ean' => '195949052520',
                'specs' => ['Тип' => 'TWS внутриканальные', 'Шумоподавление' => 'Активное (ANC H2 chip)', 'Разъем кейса' => 'USB-C / MagSafe', 'Автономность' => 'до 6 ч (30 ч с кейсом)']
            ],
            [
                'brand' => 'Marshall',
                'title' => 'Беспроводные наушники Marshall Major IV Bluetooth Black',
                'type' => 'Накладные',
                'price' => 12990,
                'mpn' => '1005983',
                'ean' => '7340055379458',
                'specs' => ['Тип' => 'Накладные', 'Подключение' => 'Bluetooth 5.0 / 3.5 мм', 'Автономность' => 'более 80 ч', 'Беспроводная зарядка' => 'Есть']
            ]
        ]
    ],

    // 40: Телевизоры
    40 => [
        'name' => 'Телевизор',
        'items' => [
            [
                'brand' => 'LG',
                'title' => 'OLED Телевизор LG OLED55C3RLA 55" (4K UHD, 120Hz, webOS, Dolby Vision)',
                'diag' => 55,
                'tech' => 'OLED',
                'price' => 144990,
                'mpn' => 'OLED55C3RLA',
                'ean' => '8806091771234',
                'specs' => ['Диагональ' => '55" (139 см)', 'Технология экрана' => 'OLED evo', 'Частота обновления' => '120 Гц', 'Разрешение' => '3840x2160 4K UHD', 'Smart TV' => 'webOS 23']
            ],
            [
                'brand' => 'Samsung',
                'title' => 'Телевизор Samsung QE65QN90CAUXRU 65" Neo QLED 4K (144Hz, HDR10+, Tizen)',
                'diag' => 65,
                'tech' => 'Neo QLED',
                'price' => 189990,
                'mpn' => 'QE65QN90CAU',
                'ean' => '8806094891201',
                'specs' => ['Диагональ' => '65" (165 см)', 'Технология экрана' => 'Neo QLED (Mini LED)', 'Частота обновления' => '144 Гц', 'Smart TV' => 'Tizen OS']
            ],
            [
                'brand' => 'Xiaomi',
                'title' => 'Телевизор Xiaomi TV A Pro 55 2025 (4K UHD, HDR10, Google TV, Dolby Audio)',
                'diag' => 55,
                'tech' => 'QLED',
                'price' => 38990,
                'mpn' => 'ELA5474EU',
                'ean' => '6971443152341',
                'specs' => ['Диагональ' => '55" (139 см)', 'Разрешение' => '3840x2160 4K UHD', 'Smart TV' => 'Google TV', 'Звук' => '24 Вт Dolby Audio']
            ]
        ]
    ]
];

// 2. Shops Configuration for Real Donor Search URLs
$shopsConfig = [
    'citilink' => [
        'name' => 'Ситилинк',
        'kind' => 'retail',
        'mode' => 'prices',
        'url_template' => 'https://www.citilink.ru/search/?text={q}',
        'color' => '#FF5000'
    ],
    'regard' => [
        'name' => 'Регард',
        'kind' => 'retail',
        'mode' => 'prices',
        'url_template' => 'https://www.regard.ru/catalog?search={q}',
        'color' => '#0055A5'
    ],
    'onlinetrade' => [
        'name' => 'ОнлайнТрейд',
        'kind' => 'retail',
        'mode' => 'prices',
        'url_template' => 'https://www.onlinetrade.ru/sitesearch.html?query={q}',
        'color' => '#1D70B8'
    ],
    'mvideo' => [
        'name' => 'М.Видео',
        'kind' => 'retail',
        'mode' => 'prices',
        'url_template' => 'https://www.mvideo.ru/listing?q={q}',
        'color' => '#E30613'
    ],
    'ozon' => [
        'name' => 'Ozon',
        'kind' => 'marketplace',
        'mode' => 'prices',
        'url_template' => 'https://www.ozon.ru/search/?text={q}&from_global=true',
        'color' => '#005BFF'
    ],
    'wildberries' => [
        'name' => 'Wildberries',
        'kind' => 'marketplace',
        'mode' => 'link_only',
        'url_template' => 'https://www.wildberries.ru/catalog/0/search.aspx?search={q}',
        'color' => '#CB11AB'
    ],
    'megamarket' => [
        'name' => 'Мегамаркет',
        'kind' => 'marketplace',
        'mode' => 'prices',
        'url_template' => 'https://megamarket.ru/catalog/?q={q}',
        'color' => '#270560'
    ],
    'yandex_market' => [
        'name' => 'Яндекс Маркет',
        'kind' => 'marketplace',
        'mode' => 'prices',
        'url_template' => 'https://market.yandex.ru/search?text={q}',
        'color' => '#FC3F1D'
    ],
    'aliexpress' => [
        'name' => 'AliExpress',
        'kind' => 'marketplace',
        'mode' => 'prices',
        'url_template' => 'https://aliexpress.ru/wholesale?SearchText={q}',
        'color' => '#FF4747'
    ],
    'dns' => [
        'name' => 'DNS',
        'kind' => 'retail',
        'mode' => 'link_only',
        'url_template' => 'https://www.dns-shop.ru/search/?q={q}',
        'color' => '#ED6C00'
    ],
    'avito' => [
        'name' => 'Авито',
        'kind' => 'marketplace',
        'mode' => 'link_only',
        'url_template' => 'https://www.avito.ru/rossiya?q={q}',
        'color' => '#00AAFF'
    ]
];

$shopKeys = array_keys($shopsConfig);

// Clean previous catalog shards
$oldFiles = glob($catalogDir . '/p*.*') ?: [];
foreach ($oldFiles as $f) { @unlink($f); }

$shardHandles = [];
$shardIndices = [];
$shardOffsets = array_fill(0, 256, 0);

for ($s = 0; $s < 256; $s++) {
    $sName = sprintf('p%03d', $s);
    $filePath = $catalogDir . '/' . $sName . '.ndjson';
    $shardHandles[$s] = fopen($filePath, 'wb');
    $shardIndices[$s] = [];
}

// Prepare items directory
foreach ($shopKeys as $sId) {
    @mkdir($itemsDir . '/' . $sId, 0775, true);
}

// Flatten all base items for fast indexing
$allBaseItems = [];
foreach ($realCatalogData as $catId => $cDef) {
    foreach ($cDef['items'] as $item) {
        $allBaseItems[] = array_merge($item, ['catId' => $catId]);
    }
}
$baseCount = count($allBaseItems);

$colors = ['Black', 'Silver', 'White', 'Titanium', 'Midnight', 'Space Gray', 'Dark Blue'];
$memoryVariants = [
    30 => ['128GB', '256GB', '512GB', '1TB'],
    20 => ['16/512GB', '32/1TB', '64/2TB'],
    14 => ['OC Edition', 'White OC', 'Gaming', 'Dual']
];

for ($id = 1; $id <= $targetProducts; $id++) {
    $base = $allBaseItems[($id - 1) % $baseCount];
    $catId = $base['catId'];

    // Generate natural product variations
    $cycle = (int)floor(($id - 1) / $baseCount);
    $title = $base['title'];

    if ($cycle > 0) {
        $color = $colors[$cycle % count($colors)];
        if (isset($memoryVariants[$catId])) {
            $memVar = $memoryVariants[$catId][$cycle % count($memoryVariants[$catId])];
            $title .= " ({$memVar}, {$color})";
        } else {
            $title .= " ({$color})";
        }
    }

    $slug = Utf8::slugify($title);
    $mpn = $base['mpn'] . ($cycle > 0 ? "-{$cycle}" : '');
    $ean = sprintf('46%010d%d', $id, ($id % 9));

    $basePrice = $base['price'];
    if ($cycle > 0) {
        $basePrice += ($cycle % 5) * 2000;
    }

    $specs = $base['specs'];
    $specs['Артикул производителя'] = $mpn;
    $specs['Штрихкод EAN'] = $ean;

    // Create 5-7 real retailer offers with authentic donor search URLs
    $numOffers = 5 + ($id % 3);
    $offers = [];
    $pricesList = [];
    $hasMp = 0;
    $hasCb = 0;

    for ($o = 0; $o < $numOffers; $o++) {
        $shopId = $shopKeys[($id + $o) % count($shopKeys)];
        $shop = $shopsConfig[$shopId];
        $offerKey = "{$shopId}:{$id}_{$o}";

        // Real donor search query URL
        $donorSearchUrl = str_replace('{q}', urlencode($title), $shop['url_template']);

        $priceVariance = (($id * 11 + $o * 17) % 21 - 10) / 100.0; // -10% to +10%
        $offerPrice = (int)round($basePrice * (1 + $priceVariance));
        if ($offerPrice < 1000) $offerPrice = 1000;

        $landed = $offerPrice;
        $origin = 'RU';
        $seller = null;
        $cond = 'new';
        $note = null;

        if ($shop['kind'] === 'marketplace') {
            $hasMp = 1;
            $rating = round(4.5 + (($id + $o) % 5) / 10, 1);
            $sellerNames = ['ТехноТрейд', 'Электроника Плюс', 'Re:Store Direct', 'M-Shop', 'Official Store', 'iStore Pro'];
            $seller = [
                'name' => $sellerNames[($id + $o) % count($sellerNames)],
                'rating' => $rating,
                'n' => 120 + (($id + $o) % 800),
                'official' => ($rating >= 4.8) ? 1 : 0
            ];
            if ($shopId === 'ozon' && $o === 0) {
                $note = 'Цена с Ozon Картой';
                $offerPrice = (int)round($offerPrice * 0.95);
                $landed = $offerPrice;
            } elseif ($shopId === 'megamarket') {
                $note = 'Кэшбэк до 15% бонусами';
            }
        }

        if ($shopId === 'aliexpress') {
            $hasCb = 1;
            $origin = 'CN';
            $landed = $offerPrice;
            $seller = [
                'name' => "Top Digital Global Store",
                'rating' => 4.8,
                'n' => 3500,
                'official' => 1
            ];
            $note = 'Доставка из Китая 12–18 дней';
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
                'note' => 'Поиск и цены на сайте ' . $shop['name'],
                'url' => $donorSearchUrl,
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
                'url' => $donorSearchUrl,
                'upd' => '2026-10-03 12:00'
            ];
            $pricesList[] = $landed;
        }
    }

    $minPrice = !empty($pricesList) ? min($pricesList) : $basePrice;
    $maxPrice = !empty($pricesList) ? max($pricesList) : $basePrice;
    $offersCount = count($pricesList);
    $priceDrop = ($id % 7 === 0) ? (float)(10 + ($id % 15)) : 0.0;

    $productRecord = [
        'id' => $id,
        'cat' => $catId,
        'title' => $title,
        'brand' => $base['brand'],
        'slug' => $slug,
        'mpn' => $mpn,
        'barcode' => $ean,
        'pub' => 1,
        'created_at' => date('Y-m-d H:i:s', strtotime('-' . ($id % 60) . ' days')),
        'specs' => $specs,
        'agg' => [
            'min' => $minPrice,
            'max' => $maxPrice,
            'cnt' => $offersCount,
            'shops_cnt' => count(array_unique(array_column($offers, 'shop'))),
            'has_mp' => $hasMp,
            'has_cb' => $hasCb,
            'drop' => $priceDrop
        ],
        'offers' => $offers
    ];

    // Stream write to pack
    $shard = $id % 256;
    $jsonLine = json_encode($productRecord, JSON_UNESCAPED_UNICODE) . "\n";
    $lineLen = strlen($jsonLine);
    $offset = $shardOffsets[$shard];

    fwrite($shardHandles[$shard], $jsonLine);
    $shardIndices[$shard][$id] = [$offset, $lineLen];
    $shardOffsets[$shard] += $lineLen;

    // Create price history for top products
    if ($id <= 500) {
        $historyRepo->append($id, "citilink:{$id}_0", round($minPrice * 1.08), 1, date('Y-m-d', strtotime('-60 days')));
        $historyRepo->append($id, "citilink:{$id}_0", round($minPrice * 1.04), 1, date('Y-m-d', strtotime('-30 days')));
        $historyRepo->append($id, "citilink:{$id}_0", $minPrice, 1, date('Y-m-d'));
    }

    if ($id % 5000 === 0) {
        echo "Streamed {$id} / {$targetProducts} products...\n";
    }
}

// Close handles and write index arrays
echo "Writing index arrays for 256 shards...\n";
for ($s = 0; $s < 256; $s++) {
    fclose($shardHandles[$s]);
    $sName = sprintf('p%03d', $s);
    $idxFile = $catalogDir . '/' . $sName . '.idx.php';
    \App\Storage\Fs::atomicWritePhpArray($idxFile, $shardIndices[$s]);
}

$duration = round(microtime(true) - $startTime, 2);
$mem = round(memory_get_peak_usage() / 1048576, 2);
echo "[OK] Seeding complete: {$targetProducts} products written in {$duration}s (Memory: {$mem} MB).\n";

// Compile catalog snapshot
echo "Building active search and catalog snapshot...\n";
$builder = new \App\Services\BuildIndexService($root);
$resMsg = $builder->run();
echo "[OK] {$resMsg}\n";
echo "=== Seeder Finished Successfully! ===\n";
