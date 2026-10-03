<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Utf8;

class OfferNormalizer
{
    private FxRatesService $fx;

    public function __construct(?FxRatesService $fx = null)
    {
        $this->fx = $fx ?: new FxRatesService();
    }

    /**
     * @param array $raw
     * @param string $shopId
     * @return array
     */
    public function normalize(array $raw, string $shopId): array
    {
        $extId = (string)($raw['id'] ?? $raw['external_id'] ?? md5((string)($raw['url'] ?? uniqid())));
        $title = trim((string)($raw['name'] ?? $raw['title'] ?? ''));

        // Clean promo junk from title
        $cleanTitle = preg_replace('/\s*\[(акция|распродажа|скидка|новинка|хит|б\/у)\]\s*/iu', ' ', $title);
        $cleanTitle = preg_replace('/\s*\((акция|скидка|подарок)\)\s*/iu', ' ', (string)$cleanTitle);
        $cleanTitle = trim((string)preg_replace('/\s+/', ' ', (string)$cleanTitle));

        $rawPrice = (float)($raw['price'] ?? 0);
        $currency = strtoupper((string)($raw['currencyId'] ?? $raw['currency'] ?? 'RUB'));
        $priceRub = $this->fx->convert($rawPrice, $currency, 'RUB');

        $rawOldPrice = isset($raw['oldprice']) ? (float)$raw['oldprice'] : null;
        $oldPriceRub = $rawOldPrice ? $this->fx->convert($rawOldPrice, $currency, 'RUB') : null;

        $inStock = true;
        if (isset($raw['available'])) {
            $inStock = filter_var($raw['available'], FILTER_VALIDATE_BOOLEAN);
        } elseif (isset($raw['in_stock'])) {
            $inStock = (bool)$raw['in_stock'];
        }

        // Clean barcode EAN/UPC
        $ean = null;
        $barcode = (string)($raw['barcode'] ?? $raw['ean'] ?? '');
        if ($barcode && preg_match('/^[0-9]{8,14}$/', $barcode)) {
            $ean = $barcode;
        }

        // Clean MPN / vendorCode
        $mpn = null;
        $vendorCode = (string)($raw['vendorCode'] ?? $raw['mpn'] ?? '');
        if ($vendorCode) {
            $mpn = strtoupper(trim(preg_replace('/[^a-zA-Z0-9\-_]/', '', $vendorCode)));
        }

        // Delivery
        $deliveryDays = (int)($raw['delivery_days'] ?? 1);
        $deliveryCost = (float)($raw['delivery_cost'] ?? 0.0);
        if ($shopId === 'aliexpress') {
            $deliveryDays = $raw['ru_stock'] ?? false ? 3 : 18;
        }

        // Params / specs extraction
        $specs = [];
        if (!empty($raw['param']) && is_array($raw['param'])) {
            foreach ($raw['param'] as $pName => $pValue) {
                $specs[trim((string)$pName)] = trim((string)$pValue);
            }
        }

        return [
            'key' => "{$shopId}:{$extId}",
            'shop' => $shopId,
            'external_id' => $extId,
            'title' => $cleanTitle ?: $title,
            'price' => $priceRub,
            'old_price' => ($oldPriceRub && $oldPriceRub > $priceRub) ? $oldPriceRub : null,
            'currency' => 'RUB',
            'url' => (string)($raw['url'] ?? ''),
            'in_stock' => $inStock,
            'delivery_days' => $deliveryDays,
            'delivery_cost' => $deliveryCost,
            'seller_name' => $raw['seller_name'] ?? null,
            'seller_rating' => isset($raw['seller_rating']) ? (float)$raw['seller_rating'] : null,
            'seller_reviews' => isset($raw['seller_reviews']) ? (int)$raw['seller_reviews'] : null,
            'ean' => $ean,
            'mpn' => $mpn,
            'vendor' => $raw['vendor'] ?? null,
            'specs' => $specs,
            'image_url' => $raw['picture'] ?? $raw['image_url'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
}
