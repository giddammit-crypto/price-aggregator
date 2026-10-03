<?php
declare(strict_types=1);

namespace App\Services;

/** Only exact, sourced SKU photos may be displayed as a product image. */
final class VerifiedProductImage
{
    private const APPROVED = [
        1 => [
            'title' => 'Процессор AMD Ryzen 7 7800X3D OEM',
            'mpn' => '100-000000910',
            'path' => '/assets/img/products/amd-ryzen-7-7800x3d-100-000000910.webp',
        ],
    ];

    /** Include verified examples in the bounded static search export. */
    public static function approvedIds(): array
    {
        return array_keys(self::APPROVED);
    }

    public static function forProduct(array $product): ?string
    {
        $entry = self::APPROVED[(int)($product['id'] ?? 0)] ?? null;
        if ($entry === null || empty($product['pub']) || ($product['title'] ?? null) !== $entry['title']
            || ($product['mpn'] ?? null) !== $entry['mpn']) {
            return null;
        }
        return is_file(dirname(__DIR__, 2) . '/public' . $entry['path']) ? $entry['path'] : null;
    }
}
