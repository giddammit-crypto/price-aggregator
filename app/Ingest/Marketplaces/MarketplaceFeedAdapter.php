<?php
declare(strict_types=1);

namespace App\Ingest\Marketplaces;

use App\Core\Config;

class MarketplaceFeedAdapter
{
    /**
     * Normalizes marketplace raw feed item into uniform format.
     */
    public static function adapt(string $marketplaceId, array $raw): array
    {
        $item = $raw;

        switch ($marketplaceId) {
            case 'ozon':
                // Ozon Card price handling
                if (isset($raw['param']['Цена с Ozon Картой'])) {
                    $item['price'] = (float)$raw['param']['Цена с Ozon Картой'];
                }
                $item['seller_name'] = $raw['seller_name'] ?? $raw['param']['Продавец'] ?? 'Ozon';
                $item['seller_rating'] = isset($raw['seller_rating']) ? (float)$raw['seller_rating'] : 4.8;
                $item['delivery_days'] = isset($raw['fbo']) && $raw['fbo'] ? 1 : 3;
                break;

            case 'megamarket':
                $item['seller_name'] = $raw['seller_name'] ?? $raw['param']['Продавец'] ?? 'Мегамаркет';
                $item['delivery_days'] = 2;
                if (isset($raw['cashback_spasibo'])) {
                    $item['specs']['Бонусы Спасибо'] = $raw['cashback_spasibo'] . '%';
                }
                break;

            case 'yandex_market':
                if (isset($raw['param']['Цена с Плюсом'])) {
                    $item['price'] = (float)$raw['param']['Цена с Плюсом'];
                }
                $item['seller_name'] = $raw['seller_name'] ?? $raw['param']['Магазин'] ?? 'Яндекс Маркет';
                $item['seller_rating'] = isset($raw['seller_rating']) ? (float)$raw['seller_rating'] : 4.7;
                $item['delivery_days'] = 1;
                break;

            case 'joom':
                $item['seller_name'] = $raw['seller_name'] ?? 'Joom Logistics';
                $item['delivery_days'] = 14;
                $item['specs']['Доставка'] = 'Из-за рубежа (14–25 дней)';
                break;
        }

        return $item;
    }
}
