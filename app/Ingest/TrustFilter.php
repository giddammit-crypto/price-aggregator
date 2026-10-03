<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Config;
use App\Core\Utf8;

class TrustFilter
{
    private array $globalSpam = [];
    private array $categoryAccessories = [];
    private float $minSellerRating = 4.3;
    private int $minSellerReviews = 50;
    private float $quarantinePriceRatio = 0.35;

    public function __construct()
    {
        $blocklist = Config::get('blocklist', []);
        $this->globalSpam = $blocklist['global_spam'] ?? [];
        $this->categoryAccessories = $blocklist['category_accessories'] ?? [];

        $trust = $blocklist['trust'] ?? [];
        $this->minSellerRating = (float)($trust['min_seller_rating'] ?? 4.3);
        $this->minSellerReviews = (int)($trust['min_seller_reviews'] ?? 50);
        $this->quarantinePriceRatio = (float)($trust['quarantine_price_ratio'] ?? 0.35);
    }

    /**
     * @param array $offer Raw or normalized offer data
     * @param int|null $categoryId Category ID if known
     * @param float|null $productMedianPrice Median price of target product if matching
     * @return array{pass: bool, action: string, reason: ?string}
     */
    public function evaluate(array $offer, ?int $categoryId = null, ?float $productMedianPrice = null): array
    {
        $title = Utf8::strtolower((string)($offer['name'] ?? $offer['title'] ?? ''));

        // 1. Global spam check (replicas, empty boxes, fakes)
        foreach ($this->globalSpam as $spamWord) {
            $spamWord = Utf8::strtolower($spamWord);
            if (str_contains($title, $spamWord)) {
                return [
                    'pass' => false,
                    'action' => 'reject',
                    'reason' => "Global spam matched: '{$spamWord}'"
                ];
            }
        }

        // 2. Category accessory check (e.g. phone case in smartphones category)
        if ($categoryId && isset($this->categoryAccessories[$categoryId])) {
            foreach ($this->categoryAccessories[$categoryId] as $accWord) {
                $accWord = Utf8::strtolower($accWord);
                if (str_contains($title, $accWord)) {
                    return [
                        'pass' => false,
                        'action' => 'reject',
                        'reason' => "Category accessory stop-word matched: '{$accWord}' for cat {$categoryId}"
                    ];
                }
            }
        }

        // 3. Marketplace merchant rating check
        if (!empty($offer['marketplace']) || !empty($offer['seller_name'])) {
            $rating = (float)($offer['seller_rating'] ?? 0);
            $reviews = (int)($offer['seller_reviews'] ?? 0);

            // If seller rating is explicitly provided and below threshold with significant reviews
            if ($rating > 0 && $rating < $this->minSellerRating && $reviews >= 10) {
                return [
                    'pass' => false,
                    'action' => 'reject',
                    'reason' => "Seller rating {$rating} is below minimum {$this->minSellerRating}"
                ];
            }
        }

        // 4. Price anomaly quarantine check
        $price = (float)($offer['price'] ?? 0);
        if ($price <= 0) {
            return [
                'pass' => false,
                'action' => 'reject',
                'reason' => 'Zero or negative price'
            ];
        }

        if ($productMedianPrice !== null && $productMedianPrice > 1000) {
            $ratio = $price / $productMedianPrice;
            if ($ratio < $this->quarantinePriceRatio) {
                return [
                    'pass' => false,
                    'action' => 'quarantine',
                    'reason' => sprintf(
                        'Price %.2f is abnormal (%.1f%% of median %.2f), quarantined for review',
                        $price,
                        $ratio * 100,
                        $productMedianPrice
                    )
                ];
            }
        }

        return [
            'pass' => true,
            'action' => 'accept',
            'reason' => null
        ];
    }
}
