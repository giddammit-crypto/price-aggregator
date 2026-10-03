<?php
declare(strict_types=1);

return [
    'name' => 'PriceHub',
    'tagline' => 'Умный агрегатор цен и скидок',
    'url' => 'http://localhost:8080',
    'env' => 'production',
    'debug' => false,
    'currency' => 'RUB',
    'timezone' => 'Europe/Moscow',
    'admin_prefix' => '/admin',
    'storage' => [
        'data_path' => dirname(__DIR__) . '/data',
        'snapshots_path' => dirname(__DIR__) . '/data/snapshots',
        'cache_path' => dirname(__DIR__) . '/data/cache'
    ]
];
