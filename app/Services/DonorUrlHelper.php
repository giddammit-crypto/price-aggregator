<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Utf8;

class DonorUrlHelper
{
    /**
     * Extracts a high-precision, clean search query for external donor stores (DNS, Ozon, WB, Citilink, etc.)
     * Removes category prefixes, synthetic variants like (BOX, White), and marketing noise.
     */
    public static function cleanModelQuery(string $title, string $brand = '', string $model = '', string $mpn = ''): string
    {
        if ($model !== '' && Utf8::strlen($model) >= 3) {
            $query = $model;
            if ($brand !== '' && stripos($query, $brand) === false) {
                $query = $brand . ' ' . $query;
            }
            return trim($query);
        }

        $clean = $title;

        // Strip Russian category nouns at the start
        $prefixes = [
            'Процессор', 'Материнская плата', 'Оперативная память', 'SSD накопитель', 'SSD-накопитель', 'Накопитель SSD',
            'Видеокарта', 'Блок питания', 'Корпус', 'Кулер для процессора', 'Система водяного охлаждения', 'СВО для процессора',
            'Игровой ноутбук', 'Ноутбук', 'Монитор', 'Готовый ПК', 'Игровой ПК', 'ПК', 'Компьютер',
            'Смартфон', 'Планшет', 'Смарт-часы', 'Фитнес-браслет',
            'Беспроводные наушники', 'Наушники', 'Игровая гарнитура',
            'Телевизор', 'Робот-пылесос'
        ];
        $cleanLower = Utf8::strtolower($clean);
        foreach ($prefixes as $p) {
            $pLower = Utf8::strtolower($p);
            if (str_starts_with($cleanLower, $pLower)) {
                $clean = trim(Utf8::substr($clean, Utf8::strlen($p)));
                break;
            }
        }

        // Remove parenthesized suffixes, e.g. (BOX, White), (2x16GB, Titanium), (Core Ultra 9 / RTX 4080...)
        $clean = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $clean);

        // Remove trailing marketing & specification noise
        $clean = preg_replace('/\s+(станция|самоочистк|влажная уборка|все в одном|with keyboard|leica camera|google tv|tizen os|vidaa os|android tv|smart tv|amoled|ips|oled|liquid retina|retina xdr).*$/iu', '', $clean);

        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        // Remove trailing common retail flags that confuse search engines: OEM, BOX, ANC, QLED, Nano IPS, etc.
        $clean = preg_replace('/\s+(OEM|BOX|ANC|QLED)$/iu', '', $clean);

        // Clean extra spaces
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        // If brand is missing from title, prepend it
        if ($brand !== '' && stripos($clean, $brand) === false) {
            $clean = $brand . ' ' . $clean;
        }

        return $clean !== '' ? $clean : $title;
    }

    /**
     * Builds the store donor search URL
     */
    public static function buildStoreUrl(string $urlTemplate, string $query): string
    {
        return str_replace('{q}', urlencode($query), $urlTemplate);
    }
}
