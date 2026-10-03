<?php
declare(strict_types=1);
return array (
  1 => 
  array (
    'id' => 1,
    'parent_id' => NULL,
    'name' => 'Комплектующие для ПК',
    'slug' => 'pc-components',
    'icon' => 'cpu',
    'children' => 
    array (
      0 => 10,
      1 => 11,
      2 => 12,
      3 => 13,
      4 => 14,
      5 => 15,
      6 => 16,
      7 => 17,
    ),
    'count' => 0,
  ),
  10 => 
  array (
    'id' => 10,
    'parent_id' => 1,
    'name' => 'Процессоры',
    'slug' => 'processors',
    'icon' => 'cpu',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'socket',
      2 => 'cores',
      3 => 'threads',
      4 => 'tdp',
    ),
    'count' => 2664,
  ),
  11 => 
  array (
    'id' => 11,
    'parent_id' => 1,
    'name' => 'Материнские платы',
    'slug' => 'motherboards',
    'icon' => 'circuit-board',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'socket',
      2 => 'chipset',
      3 => 'form_factor',
    ),
    'count' => 0,
  ),
  12 => 
  array (
    'id' => 12,
    'parent_id' => 1,
    'name' => 'Оперативная память',
    'slug' => 'ram',
    'icon' => 'layers',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'type',
      2 => 'capacity_gb',
      3 => 'frequency_mhz',
    ),
    'count' => 0,
  ),
  13 => 
  array (
    'id' => 13,
    'parent_id' => 1,
    'name' => 'SSD и накопители',
    'slug' => 'storage-ssd',
    'icon' => 'hard-drive',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'form_factor',
      2 => 'capacity_gb',
      3 => 'interface',
    ),
    'count' => 0,
  ),
  14 => 
  array (
    'id' => 14,
    'parent_id' => 1,
    'name' => 'Видеокарты',
    'slug' => 'graphics-cards',
    'icon' => 'tv',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'gpu_chip',
      2 => 'mem_gb',
      3 => 'interface',
    ),
    'count' => 5336,
  ),
  15 => 
  array (
    'id' => 15,
    'parent_id' => 1,
    'name' => 'Блоки питания',
    'slug' => 'power-supplies',
    'icon' => 'zap',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'power_watt',
      2 => 'certificate',
    ),
    'count' => 0,
  ),
  16 => 
  array (
    'id' => 16,
    'parent_id' => 1,
    'name' => 'Корпуса',
    'slug' => 'cases',
    'icon' => 'box',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'form_factor',
      2 => 'color',
    ),
    'count' => 0,
  ),
  17 => 
  array (
    'id' => 17,
    'parent_id' => 1,
    'name' => 'Охлаждение ПК',
    'slug' => 'cooling',
    'icon' => 'wind',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'type',
      2 => 'socket',
    ),
    'count' => 0,
  ),
  2 => 
  array (
    'id' => 2,
    'parent_id' => NULL,
    'name' => 'Ноутбуки и компьютеры',
    'slug' => 'laptops-computers',
    'icon' => 'laptop',
    'children' => 
    array (
      0 => 20,
      1 => 21,
      2 => 22,
    ),
    'count' => 0,
  ),
  20 => 
  array (
    'id' => 20,
    'parent_id' => 2,
    'name' => 'Ноутбуки',
    'slug' => 'laptops',
    'icon' => 'laptop',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'screen_size',
      2 => 'cpu_type',
      3 => 'ram_gb',
      4 => 'ssd_gb',
    ),
    'count' => 2668,
  ),
  21 => 
  array (
    'id' => 21,
    'parent_id' => 2,
    'name' => 'Мониторы',
    'slug' => 'monitors',
    'icon' => 'monitor',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'diagonal',
      2 => 'resolution',
      3 => 'hz',
      4 => 'matrix',
    ),
    'count' => 0,
  ),
  22 => 
  array (
    'id' => 22,
    'parent_id' => 2,
    'name' => 'Готовые ПК',
    'slug' => 'desktop-pcs',
    'icon' => 'server',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'cpu_type',
      2 => 'gpu_chip',
      3 => 'ram_gb',
    ),
    'count' => 0,
  ),
  3 => 
  array (
    'id' => 3,
    'parent_id' => NULL,
    'name' => 'Смартфоны и гаджеты',
    'slug' => 'smartphones-gadgets',
    'icon' => 'smartphone',
    'children' => 
    array (
      0 => 30,
      1 => 31,
      2 => 32,
      3 => 33,
    ),
    'count' => 0,
  ),
  30 => 
  array (
    'id' => 30,
    'parent_id' => 3,
    'name' => 'Смартфоны',
    'slug' => 'smartphones',
    'icon' => 'smartphone',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'rom_gb',
      2 => 'ram_gb',
      3 => 'color',
      4 => 'origin',
    ),
    'count' => 5336,
  ),
  31 => 
  array (
    'id' => 31,
    'parent_id' => 3,
    'name' => 'Планшеты',
    'slug' => 'tablets',
    'icon' => 'tablet',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'screen_size',
      2 => 'rom_gb',
    ),
    'count' => 0,
  ),
  32 => 
  array (
    'id' => 32,
    'parent_id' => 3,
    'name' => 'Смарт-часы и браслеты',
    'slug' => 'smartwatches',
    'icon' => 'watch',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'color',
      2 => 'os',
    ),
    'count' => 0,
  ),
  33 => 
  array (
    'id' => 33,
    'parent_id' => 3,
    'name' => 'Наушники и гарнитуры',
    'slug' => 'headphones',
    'icon' => 'headphones',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'type',
      2 => 'wireless',
      3 => 'anc',
    ),
    'count' => 1998,
  ),
  4 => 
  array (
    'id' => 4,
    'parent_id' => NULL,
    'name' => 'ТВ и умный дом',
    'slug' => 'tv-smart-home',
    'icon' => 'tv',
    'children' => 
    array (
      0 => 40,
      1 => 50,
    ),
    'count' => 0,
  ),
  40 => 
  array (
    'id' => 40,
    'parent_id' => 4,
    'name' => 'Телевизоры',
    'slug' => 'televisions',
    'icon' => 'tv',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'diagonal',
      2 => 'resolution',
      3 => 'smart_tv',
    ),
    'count' => 1998,
  ),
  50 => 
  array (
    'id' => 50,
    'parent_id' => 4,
    'name' => 'Роботы-пылесосы',
    'slug' => 'robot-vacuums',
    'icon' => 'disc',
    'facets' => 
    array (
      0 => 'brand',
      1 => 'wet_cleaning',
      2 => 'station',
    ),
    'count' => 0,
  ),
);
