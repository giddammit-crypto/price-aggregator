<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Utf8;
use RuntimeException;

/**
 * Standard YML / XML Partner Product Feed Validator.
 * Validates feed structure, currency declarations, category mapping,
 * and compliance of all required offer tags (<name>, <price>, <oldprice>, <currencyId>, <vendor>, <model>, <url>, <picture>).
 */
class FeedValidator
{
    public const REQUIRED_TAGS = [
        'name',
        'price',
        'oldprice',
        'currencyId',
        'vendor',
        'model',
        'url',
        'picture'
    ];

    public const VALID_CURRENCIES = ['RUR', 'RUB', 'USD', 'EUR', 'BYN', 'KZT', 'CNY'];

    private YmlReader $reader;

    public function __construct(?YmlReader $reader = null)
    {
        $this->reader = $reader ?: new YmlReader();
    }

    /**
     * Validates an entire XML / YML feed file against standards.
     *
     * @param string $filePath Absolute or relative path to feed
     * @param bool $strictTags Require all 8 standard tags (<name>, <price>, <oldprice>, <currencyId>, <vendor>, <model>, <url>, <picture>)
     * @return array{
     *     valid: bool,
     *     shop_name: ?string,
     *     shop_url: ?string,
     *     currencies: list<string>,
     *     categories_count: int,
     *     total_offers: int,
     *     valid_offers: int,
     *     invalid_offers: int,
     *     errors: list<string>,
     *     warnings: list<string>,
     *     tag_stats: array<string, int>
     * }
     */
    public function validateFeedFile(string $filePath, bool $strictTags = true): array
    {
        $errors = [];
        $warnings = [];

        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'shop_name' => null,
                'shop_url' => null,
                'currencies' => [],
                'categories_count' => 0,
                'total_offers' => 0,
                'valid_offers' => 0,
                'invalid_offers' => 0,
                'errors' => ["Feed file does not exist: {$filePath}"],
                'warnings' => [],
                'tag_stats' => []
            ];
        }

        $contentHeader = (string)file_get_contents($filePath, false, null, 0, 16384);
        if ($contentHeader === '') {
            return [
                'valid' => false,
                'shop_name' => null,
                'shop_url' => null,
                'currencies' => [],
                'categories_count' => 0,
                'total_offers' => 0,
                'valid_offers' => 0,
                'invalid_offers' => 0,
                'errors' => ["Feed file is empty: {$filePath}"],
                'warnings' => [],
                'tag_stats' => []
            ];
        }

        // 1. Structure: Header check
        if (!str_contains($contentHeader, '<?xml') && !str_contains($contentHeader, '<yml_catalog')) {
            $errors[] = 'Missing standard XML declaration (<?xml) or <yml_catalog> root element';
        }

        // Shop name & URL
        $shopName = null;
        if (preg_match('/<shop>\s*<name>(.*?)<\/name>/is', $contentHeader, $m)) {
            $shopName = trim($m[1]);
        }
        $shopUrl = null;
        if (preg_match('/<shop>.*?<url>(.*?)<\/url>/is', $contentHeader, $m)) {
            $shopUrl = trim($m[1]);
        }

        // Currencies declared
        $declaredCurrencies = [];
        if (preg_match('/<currencies>(.*?)<\/currencies>/is', $contentHeader, $cm)) {
            if (preg_match_all('/<currency\s+id=["\']([^"\']+)["\']/is', $cm[1], $currMatches)) {
                $declaredCurrencies = array_unique($currMatches[1]);
            }
        }
        if (empty($declaredCurrencies)) {
            $warnings[] = 'No <currencies> block detected in feed header';
        }

        // Categories declared
        $categoryCount = 0;
        if (preg_match('/<categories>(.*?)<\/categories>/is', $contentHeader, $catBlock)) {
            $categoryCount = preg_match_all('/<category\s+id=["\']\d+["\']/is', $catBlock[1]);
        }

        // 2. Stream and validate offers
        $totalOffers = 0;
        $validOffers = 0;
        $invalidOffers = 0;
        $tagStats = array_fill_keys(self::REQUIRED_TAGS, 0);

        try {
            foreach ($this->reader->readOffers($filePath) as $rawOffer) {
                $totalOffers++;
                $offerCheck = $this->validateOffer($rawOffer, $declaredCurrencies, $strictTags);

                // Update tag presence stats
                foreach (self::REQUIRED_TAGS as $tag) {
                    if (!empty($rawOffer[$tag]) || ($tag === 'picture' && !empty($rawOffer['picture']))) {
                        $tagStats[$tag]++;
                    }
                }

                if ($offerCheck['valid']) {
                    $validOffers++;
                } else {
                    $invalidOffers++;
                    foreach ($offerCheck['errors'] as $err) {
                        if (count($errors) < 25) { // Limit error explosion in report
                            $errors[] = "Offer '{$rawOffer['id']}': {$err}";
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $errors[] = 'Feed streaming error: ' . $e->getMessage();
        }

        if ($totalOffers === 0) {
            $errors[] = 'Feed contains zero offers';
        }

        $isValid = empty($errors) && ($invalidOffers === 0);

        return [
            'valid' => $isValid,
            'shop_name' => $shopName,
            'shop_url' => $shopUrl,
            'currencies' => array_values($declaredCurrencies),
            'categories_count' => $categoryCount,
            'total_offers' => $totalOffers,
            'valid_offers' => $validOffers,
            'invalid_offers' => $invalidOffers,
            'errors' => $errors,
            'warnings' => $warnings,
            'tag_stats' => $tagStats
        ];
    }

    /**
     * Validates a single parsed offer record.
     *
     * @param array $offer
     * @param list<string> $allowedCurrencies
     * @param bool $strictTags
     * @return array{valid: bool, errors: list<string>, missing_tags: list<string>}
     */
    public function validateOffer(array $offer, array $allowedCurrencies = [], bool $strictTags = true): array
    {
        $errors = [];
        $missingTags = [];

        // ID
        $id = trim((string)($offer['id'] ?? ''));
        if ($id === '') {
            $errors[] = 'Offer ID is empty or missing';
        }

        // Required tag check
        if ($strictTags) {
            foreach (self::REQUIRED_TAGS as $tag) {
                $val = $offer[$tag] ?? null;
                if ($val === null || (is_string($val) && trim($val) === '') || (is_numeric($val) && (float)$val <= 0 && $tag !== 'oldprice')) {
                    $missingTags[] = $tag;
                    $errors[] = "Missing or empty required tag <{$tag}>";
                }
            }
        }

        // Price
        $price = (float)($offer['price'] ?? 0);
        if ($price <= 0) {
            if (!in_array('price', $missingTags, true)) {
                $errors[] = "Price must be positive (got {$price})";
            }
        }

        // Old Price
        if (isset($offer['oldprice']) && $offer['oldprice'] !== null) {
            $oldPrice = (float)$offer['oldprice'];
            if ($oldPrice < $price) {
                $errors[] = "oldprice ({$oldPrice}) must be greater than or equal to price ({$price})";
            }
        }

        // Currency
        $currencyId = strtoupper(trim((string)($offer['currencyId'] ?? '')));
        if ($currencyId === '') {
            if (!in_array('currencyId', $missingTags, true)) {
                $errors[] = 'currencyId tag is empty';
            }
        } else {
            $validList = !empty($allowedCurrencies) ? array_map('strtoupper', $allowedCurrencies) : self::VALID_CURRENCIES;
            if (!in_array($currencyId, $validList, true) && !in_array($currencyId, self::VALID_CURRENCIES, true)) {
                $errors[] = "currencyId '{$currencyId}' is not declared or recognized";
            }
        }

        // URL
        $url = trim((string)($offer['url'] ?? ''));
        if ($url !== '') {
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $errors[] = "URL must start with http:// or https:// (got '{$url}')";
            }
        }

        // Picture
        $picture = trim((string)($offer['picture'] ?? ''));
        if ($picture === '' && !in_array('picture', $missingTags, true)) {
            $errors[] = 'Picture is empty';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'missing_tags' => $missingTags
        ];
    }
}
