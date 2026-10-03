<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', $root . '/app');

use App\Services\VerifiedProductImage;
use App\Storage\Pack;

$product = Pack::get(1);
if (!$product || VerifiedProductImage::forProduct($product) !== '/assets/img/products/amd-ryzen-7-7800x3d-100-000000910.webp') {
    throw new RuntimeException('Approved photo must match the published base model');
}
foreach (['mpn', 'title', 'id'] as $field) {
    $other = $product;
    $other[$field] = $field === 'id' ? 2 : 'different';
    if (VerifiedProductImage::forProduct($other) !== null) {
        throw new RuntimeException("Photo must not be shown for a mismatched {$field}");
    }
}
echo "[PASS] Verified photo only for the exact model and MPN\n";
