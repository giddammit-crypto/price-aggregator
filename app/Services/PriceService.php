<?php
/**
 * Price & Offer Calculation Service
 */

declare(strict_types=1);

namespace App\Services;

class PriceService
{
    public static function calculateLanded(int|float $basePrice, string $currency, float $fxRate, int $deliveryRub = 0): int
    {
        $rub = ($currency === 'RUB') ? (float)$basePrice : ($basePrice * $fxRate);
        return (int)round($rub + $deliveryRub);
    }

    public static function findBestOffer(array $offers): ?array
    {
        $best = null;
        $lowestPrice = PHP_INT_MAX;

        foreach ($offers as $offer) {
            if (($offer['mode'] ?? 'prices') === 'link_only' || empty($offer['landed'])) {
                continue;
            }
            $p = (int)$offer['landed'];
            if ($p < $lowestPrice) {
                $lowestPrice = $p;
                $best = $offer;
            }
        }
        return $best;
    }

    public static function groupOffersByKind(array $offers): array
    {
        $grouped = [
            'all' => $offers,
            'retail' => [],
            'marketplace' => [],
            'crossborder' => [],
            'link_only' => []
        ];

        foreach ($offers as $o) {
            $kind = $o['kind'] ?? 'retail';
            $mode = $o['mode'] ?? 'prices';

            if ($mode === 'link_only') {
                $grouped['link_only'][] = $o;
            } elseif ($kind === 'crossborder') {
                $grouped['crossborder'][] = $o;
            } elseif ($kind === 'marketplace') {
                $grouped['marketplace'][] = $o;
            } else {
                $grouped['retail'][] = $o;
            }
        }

        return $grouped;
    }
}
