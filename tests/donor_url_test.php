<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');

use App\Services\DonorUrlHelper;

$testSamples = [
    ['AMD', 'Процессор AMD Ryzen 7 7800X3D OEM (BOX, White)'],
    ['Intel', 'Процессор Intel Core i5-12400F OEM (OEM, Dark Blue)'],
    ['ASUS', 'Материнская плата ASUS TUF GAMING B650-PLUS (Titanium)'],
    ['Kingston', 'Оперативная память Kingston FURY Beast DDR5 32GB (2x16GB) 6000MHz'],
    ['Samsung', 'SSD накопитель Samsung 990 PRO 2TB NVMe M.2'],
    ['Palit', 'Видеокарта Palit GeForce RTX 4090 GameRock 24GB'],
    ['MSI', 'Видеокарта MSI GeForce RTX 4060 Ventus 2X Black 8G OC'],
    ['Corsair', 'Блок питания Corsair RM850x 850W Gold (Dark Blue)'],
    ['DeepCool', 'Кулер для процессора DeepCool AK620 Digital'],
    ['Apple', 'Ноутбук Apple MacBook Air 13 M3 16GB 512GB Midnight'],
    ['Apple', 'Смартфон Apple iPhone 15 128GB Black'],
    ['Samsung', 'Смартфон Samsung Galaxy S24 Ultra 256GB Titanium Gray'],
    ['Sony', 'Беспроводные наушники Sony WH-1000XM5 Black ANC'],
    ['Apple', 'Беспроводные наушники Apple AirPods Pro 2 (USB-C) White'],
    ['Roborock', 'Робот-пылесос Roborock S8 Pro Ultra White станция самоочистки']
];

foreach ($testSamples as $t) {
    $clean = DonorUrlHelper::cleanModelQuery($t[1], $t[0]);
    echo sprintf("%-65s => %s\n", $t[1], $clean);
}
