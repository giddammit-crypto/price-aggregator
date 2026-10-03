<?php
declare(strict_types=1);

return [
    1 => [
        'id' => 1,
        'parent_id' => null,
        'name' => 'Комплектующие для ПК',
        'slug' => 'pc-components',
        'icon' => 'cpu',
        'children' => [10, 11, 12, 13, 14, 15, 16, 17]
    ],
    10 => [
        'id' => 10,
        'parent_id' => 1,
        'name' => 'Процессоры',
        'slug' => 'processors',
        'icon' => 'cpu',
        'facets' => ['brand', 'socket', 'cores', 'threads', 'tdp']
    ],
    11 => [
        'id' => 11,
        'parent_id' => 1,
        'name' => 'Материнские платы',
        'slug' => 'motherboards',
        'icon' => 'circuit-board',
        'facets' => ['brand', 'socket', 'chipset', 'form_factor']
    ],
    12 => [
        'id' => 12,
        'parent_id' => 1,
        'name' => 'Оперативная память',
        'slug' => 'ram',
        'icon' => 'layers',
        'facets' => ['brand', 'type', 'capacity_gb', 'frequency_mhz']
    ],
    13 => [
        'id' => 13,
        'parent_id' => 1,
        'name' => 'SSD и накопители',
        'slug' => 'storage-ssd',
        'icon' => 'hard-drive',
        'facets' => ['brand', 'form_factor', 'capacity_gb', 'interface']
    ],
    14 => [
        'id' => 14,
        'parent_id' => 1,
        'name' => 'Видеокарты',
        'slug' => 'graphics-cards',
        'icon' => 'gpu',
        'facets' => ['brand', 'gpu_chip', 'mem_gb', 'interface']
    ],
    15 => [
        'id' => 15,
        'parent_id' => 1,
        'name' => 'Блоки питания',
        'slug' => 'power-supplies',
        'icon' => 'zap',
        'facets' => ['brand', 'power_watt', 'certificate']
    ],
    16 => [
        'id' => 16,
        'parent_id' => 1,
        'name' => 'Корпуса',
        'slug' => 'cases',
        'icon' => 'box',
        'facets' => ['brand', 'form_factor', 'color']
    ],
    17 => [
        'id' => 17,
        'parent_id' => 1,
        'name' => 'Охлаждение ПК',
        'slug' => 'cooling',
        'icon' => 'wind',
        'facets' => ['brand', 'type', 'socket']
    ],
    2 => [
        'id' => 2,
        'parent_id' => null,
        'name' => 'Ноутбуки и компьютеры',
        'slug' => 'laptops-computers',
        'icon' => 'laptop',
        'children' => [20, 21, 22]
    ],
    20 => [
        'id' => 20,
        'parent_id' => 2,
        'name' => 'Ноутбуки',
        'slug' => 'laptops',
        'icon' => 'laptop',
        'facets' => ['brand', 'screen_size', 'cpu_type', 'ram_gb', 'ssd_gb']
    ],
    21 => [
        'id' => 21,
        'parent_id' => 2,
        'name' => 'Мониторы',
        'slug' => 'monitors',
        'icon' => 'monitor',
        'facets' => ['brand', 'diagonal', 'resolution', 'hz', 'matrix']
    ],
    22 => [
        'id' => 22,
        'parent_id' => 2,
        'name' => 'Готовые ПК',
        'slug' => 'desktop-pcs',
        'icon' => 'server',
        'facets' => ['brand', 'cpu_type', 'gpu_chip', 'ram_gb']
    ],
    3 => [
        'id' => 3,
        'parent_id' => null,
        'name' => 'Смартфоны и гаджеты',
        'slug' => 'smartphones-gadgets',
        'icon' => 'smartphone',
        'children' => [30, 31, 32, 33]
    ],
    30 => [
        'id' => 30,
        'parent_id' => 3,
        'name' => 'Смартфоны',
        'slug' => 'smartphones',
        'icon' => 'smartphone',
        'facets' => ['brand', 'rom_gb', 'ram_gb', 'color', 'origin']
    ],
    31 => [
        'id' => 31,
        'parent_id' => 3,
        'name' => 'Планшеты',
        'slug' => 'tablets',
        'icon' => 'tablet',
        'facets' => ['brand', 'screen_size', 'rom_gb']
    ],
    32 => [
        'id' => 32,
        'parent_id' => 3,
        'name' => 'Смарт-часы и браслеты',
        'slug' => 'smartwatches',
        'icon' => 'watch',
        'facets' => ['brand', 'color', 'os']
    ],
    33 => [
        'id' => 33,
        'parent_id' => 3,
        'name' => 'Наушники и гарнитуры',
        'slug' => 'headphones',
        'icon' => 'headphones',
        'facets' => ['brand', 'type', 'wireless', 'anc']
    ],
    4 => [
        'id' => 4,
        'parent_id' => null,
        'name' => 'ТВ и умный дом',
        'slug' => 'tv-smart-home',
        'icon' => 'tv',
        'children' => [40, 50]
    ],
    40 => [
        'id' => 40,
        'parent_id' => 4,
        'name' => 'Телевизоры',
        'slug' => 'televisions',
        'icon' => 'tv',
        'facets' => ['brand', 'diagonal', 'resolution', 'smart_tv']
    ],
    50 => [
        'id' => 50,
        'parent_id' => 4,
        'name' => 'Роботы-пылесосы',
        'slug' => 'robot-vacuums',
        'icon' => 'disc',
        'facets' => ['brand', 'wet_cleaning', 'station']
    ]
];
