<?php
declare(strict_types=1);

namespace App\Services;

class PriceAlertService
{
    public static function isEligible(array $subscription, ?array $product): bool
    {
        if (!$product || empty($product['pub']) || (int)($subscription['product_id'] ?? 0) !== (int)($product['id'] ?? 0)) {
            return false;
        }

        $target = (float)($subscription['target_price'] ?? 0);
        $current = (float)($product['agg']['min'] ?? 0);
        return is_finite($target) && is_finite($current)
            && $target > 0 && $current > 0 && $current <= $target;
    }
}
