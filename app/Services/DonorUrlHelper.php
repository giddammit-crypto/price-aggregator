<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Utf8;

class DonorUrlHelper
{
    /**
     * Extracts a high-precision, clean search query for external donor stores (Wildberries, DNS, Ozon, Citilink, etc.)
     * Removes category prefixes, synthetic variants, internal specifications, and marketing noise.
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

        // Remove parenthesized suffixes, e.g. (BOX, White), (2x16GB, Titanium), (TDP 260 Вт)
        $clean = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $clean);

        // Remove Prebuilt PC slash specs e.g. 'Core i5 / RTX 4060 / 16GB...'
        $clean = preg_replace('/\s+(Core|Ryzen|RTX|GTX|Intel|AMD)\s*i?[0-9]?\s*\/.*$/iu', '', $clean);

        // Remove technical monitor suffixes e.g. '27 165Hz WQHD Curved 1000R'
        $clean = preg_replace('/\s+\d{2}(\.\d)?\s+(Nano\s+)?(IPS|VA|OLED|QHD|FHD|UWQHD|144Hz|165Hz|170Hz|240Hz).*$/iu', '', $clean);

        // Remove robot vacuum marketing phrases
        $clean = preg_replace('/\s+(станция|самоочистк|влажная уборка|с горячей водой|со станцией).*$/iu', '', $clean);

        // Remove TV marketing suffixes while preserving the TV model code
        $clean = preg_replace('/\s+\d{2}\s+(The One|Ambilight|Full Array|webOS|Smart TV|Google TV|VIDAA|Tizen|Mini-LED|4K|120Hz|144Hz|QLED|Android TV).*$/iu', '', $clean);

        // Remove RAM kit frequency e.g. '32GB 6000MHz' -> '32GB'
        $clean = preg_replace('/(\d+GB)\s+\d{4,5}MHz/iu', '$1', $clean);

        // Remove SSD interface noise e.g. 'NVMe M.2'
        $clean = preg_replace('/\s+(NVMe|M\.2|PCIe).*$/iu', '', $clean);

        // Remove GPU edition and suffix noise e.g. 'Black 8G OC', '24GB', '16GB White'
        $clean = preg_replace('/\s+(Black\s+8G\s+OC|24GB|16GB\s+White|12GB|Gaming\s+OC\s+16GB|Super\s+16GB|Twin\s+Edge\s+12GB|Twin\s+X2\s+12GB|Ultra\s+W\s+Duo\s+OC\s+8GB)$/iu', '', $clean);

        // Remove smartwatch case materials and GPS e.g. 'GPS 45mm Midnight Aluminum' -> '45mm'
        $clean = preg_replace('/\s+GPS\s+/iu', ' ', $clean);
        $clean = preg_replace('/\s+(Midnight\s+Aluminum|Stainless\s+Steel|Sapphire\s+Solar|Carbon\s+Gray|Graphite|Black)$/iu', '', $clean);

        // Remove compound mobile color suffixes
        $clean = preg_replace('/\s+(Desert\s+Titanium|Titanium\s+Gray|Space\s+Gray|Gravity\s+Gray|Alps\s+Snowy|Vintage\s+Green|Silky\s+Black|Epi\s+Green|Fluid\s+Silver|Storm\s+Grey|Natural\s+Titanium|Blue\s+Titanium|Black\s+Titanium|White\s+Titanium)$/iu', '', $clean);
        $clean = preg_replace('/\s+(Black|White|Silver|Titanium|Midnight|Graphite|Gold|Yellow)$/iu', '', $clean);

        // Remove Wi-Fi / GPS / wireless / accessory suffixes at the end
        $clean = preg_replace('/\s+(Wi-Fi|GPS|with Pen|with Keyboard|Stainless Steel|Sapphire Solar|Wireless|Bluetooth|ANC|Black\/Red|Edition)$/iu', '', $clean);

        // Clean extra spaces before trailing packaging check
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        // Remove common retail packaging flags: OEM, BOX, Modular
        $clean = preg_replace('/\s+(OEM|BOX|Modular)$/iu', '', $clean);

        // Clean extra spaces
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        // If brand is missing from title, prepend it (avoid duplicating Western Digital with WD)
        if ($brand !== '' && stripos($clean, $brand) === false) {
            if (!($brand === 'Western Digital' && (str_starts_with($clean, 'WD') || stripos($clean, 'WD ') !== false))) {
                $clean = $brand . ' ' . $clean;
            }
        }

        return $clean !== '' ? $clean : $title;
    }

    /**
     * Builds the store donor search URL.
     * Uses rawurlencode (RFC 3986 with %20) instead of urlencode (+)
     * which is required for Wildberries, Ozon, and modern single-page applications.
     */
    public static function buildStoreUrl(string $urlTemplate, string $query): string
    {
        return str_replace('{q}', rawurlencode($query), $urlTemplate);
    }
}
