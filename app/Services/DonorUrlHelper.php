<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Utf8;

class DonorUrlHelper
{
    /**
     * Extracts a high-precision, clean search query for external donor stores (Wildberries, DNS, Ozon, Citilink, etc.)
     * Removes category prefixes, synthetic variants, internal specifications, and marketing noise across all 17 categories.
     *
     * @param string $title Full product title
     * @param string $brand Brand name
     * @param string $model Explicit model string (if available)
     * @param string $mpn Manufacturer part number (if available)
     * @return string Cleaned, high-precision search query
     */
    public static function cleanModelQuery(string $title, string $brand = '', string $model = '', string $mpn = ''): string
    {
        // Use model if explicitly provided and meaningful, otherwise clean the title
        $clean = ($model !== '' && Utf8::strlen(trim($model)) >= 3) ? $model : $title;

        // 1. Strip Russian category prefixes at the beginning (longest matches first)
        $prefixes = [
            'Комплект оперативной памяти', 'Система жидкостного охлаждения', 'Система водяного охлаждения',
            'Внешний жесткий диск', 'Твердотельный накопитель', 'Беспроводные наушники', 'Кулер для процессора',
            'СЖО для процессора', 'СВО для процессора', 'Графический ускоритель', 'Мобильный телефон',
            'Оперативная память', 'SSD-накопитель', 'SSD накопитель', 'Накопитель SSD', 'Игровая гарнитура',
            'Игровой ноутбук', 'Фитнес-браслет', 'Робот-пылесос', 'Робот пылесос', 'Пылесос-робот', 'Пылесос робот',
            'Материнская плата', 'Системный блок', 'Внутренний SSD', 'Умный браслет', 'TWS-наушники', 'TWS наушники',
            'Корпус для ПК', 'Модуль памяти', 'Внешний SSD', 'Жесткий диск', 'HDD накопитель',
            'Блок питания', 'Ультрабук', 'Готовый ПК', 'Игровой ПК', 'Смарт-часы', 'Умные часы',
            'Видеокарта', 'Процессор', 'Телевизор', 'Компьютер', 'Моноблок', 'Наушники', 'Гарнитура',
            'SSD диск', 'Ноутбук', 'Монитор', 'Смартфон', 'Планшет', 'Браслет', 'Десктоп',
            'Телефон', 'Корпус', 'Кулер', 'СЖО', 'СВО', 'ТВ', 'ПК'
        ];

        $cleanLower = Utf8::strtolower($clean);
        foreach ($prefixes as $p) {
            $pLower = Utf8::strtolower($p);
            if (str_starts_with($cleanLower, $pLower)) {
                $candidate = trim(Utf8::substr($clean, Utf8::strlen($p)));
                if ($candidate !== '') {
                    $clean = $candidate;
                    break;
                }
            }
        }

        // 2. Remove parenthesized suffixes, e.g. (BOX, White), (2x16GB, Titanium), (TDP 260 Вт)
        $clean = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $clean);
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        // 3. Prebuilt PC and laptop slash component specifications: e.g. 'Core i5 / RTX 4060 / 16GB DDR5 / 1TB SSD'
        $clean = preg_replace('/\s+(?:Intel\s+|AMD\s+)?(?:Core|Ryzen|RTX|GTX)\b[^\/]*\/.*$/iu', '', $clean);

        // 4. Laptop slash specs if in brackets or trailing
        $clean = preg_replace('/\s+\((?:Core|Ryzen|Intel|AMD|RTX).*?\)/iu', '', $clean);

        // 5. Monitors display specs
        // a. Model code with digits followed by repeated diagonal + matrix/hz/res e.g. '27GP850-B 27 Nano IPS 165Hz QHD'
        $clean = preg_replace('/(\b[A-Za-z0-9-]*\d+[A-Za-z0-9-]*)\s+\d{2}(?:\.\d)?\s+(?:(?:Nano|Fast|Rapid)\s+)?(?:IPS|VA|OLED|QHD|WQHD|UWQHD|FHD|4K|144Hz|165Hz|170Hz|240Hz).*$/iu', '$1', $clean);
        // b. Trailing composite refresh rate and curvature specs e.g. '144Hz UWQHD', '165Hz FHD', 'Curved 1000R'
        $clean = preg_replace('/\s+(?:144Hz\s+UWQHD|165Hz\s+FHD|Curved\s+\d+R)$/iu', '', $clean);

        // 6. TVs model code and marketing specs
        // a. Model code with digits followed by repeated diagonal and marketing specs:
        // e.g. 'LG OLED55C3RLA 55 4K 120Hz webOS Smart TV' -> 'LG OLED55C3RLA'
        $clean = preg_replace('/(\b[A-Za-z0-9-]*\d+[A-Za-z0-9-]*)\s+\d{2}\s+(?:The One|Ambilight|Full Array|webOS|Smart TV|Google TV|VIDAA|Tizen|Mini-LED|4K|120Hz|144Hz|QLED|Android TV).*$/iu', '$1', $clean);
        // b. TV trailing tech specs (4K UHD, QLED, Google TV, Android TV, etc.):
        // e.g. 'Xiaomi TV A Pro 55 2025 4K UHD QLED Google TV' -> 'Xiaomi TV A Pro 55 2025'
        // e.g. 'Haier 55 Smart TV S3 4K UHD Android TV' -> 'Haier 55 Smart TV S3'
        $clean = preg_replace('/\s+(?:4K\s+UHD|4K|8K|QLED|Mini-LED|DLED|Full Array(?:\s+LED)?|Ambilight|The One|HDR\d*\+?|120Hz|144Hz|60Hz|Google\s+TV|Android\s+TV|webOS(?:\s+Smart\s+TV)?|Tizen(?:\s+OS)?|VIDAA(?:\s+OS)?).*$/iu', '', $clean);

        // 7. RAM kit frequency e.g. '32GB 6000MHz' -> '32GB'
        $clean = preg_replace('/(\d+GB)\s+\d{4,5}MHz/iu', '$1', $clean);

        // 8. SSD interface noise e.g. 'NVMe M.2', 'PCIe 4.0 x4'
        $clean = preg_replace('/\s+(?:NVMe|M\.2|PCIe(?:\s+[0-9.]+\s*x\d+)?).*$/iu', '', $clean);

        // 9. GPU memory/edition noise (preserves core chip suffixes: Super, Ti, XT, XTX)
        // e.g. 'Black 8G OC', '24GB', '16GB White', '12GB', '8GB'
        $clean = preg_replace('/\s+(?:Black\s+8G\s+OC|8G\s+OC|24GB|16GB(?:\s+White)?|12GB|8GB)$/iu', '', $clean);

        // 10. Robot vacuum marketing phrases, station descriptors, and suction power
        $clean = preg_replace('/\s+(?:(?:WiFi|Wi-Fi)\s+)?(?:станция(?:\s+(?:самоочистки|с\s+промывкой\s+швабр|Все\s+в\s+одном|всасывания\s+пыли))?|со\s+станцией(?:\s+(?:самоочистки|всасывания\s+пыли))?|влажная\s+уборка(?:\s+\d+\s*Па)?|с\s+горячей\s+водой(?:\s+\d+\s*Па)?|\d+\s*Па).*$/iu', '', $clean);

        // 11. Multi-pass loop for stripping trailing colors, packaging, accessories, and audio marketing suffixes
        for ($pass = 0; $pass < 4; $pass++) {
            $clean = trim(preg_replace('/\s+/u', ' ', $clean));
            $prev = $clean;

            // Smartwatch GPS, case materials
            $clean = preg_replace('/\s+GPS\b/iu', '', $clean);
            $clean = preg_replace('/\s+(?:Midnight\s+Aluminum|Stainless\s+Steel|Sapphire\s+Solar|Carbon\s+Gray|Graphite|Black\s+Stainless\s+Steel)$/iu', '', $clean);

            // Mobile/Camera/Display marketing tech suffixes at end
            $clean = preg_replace('/\s+(?:Leica\s+Camera|AMOLED|with\s+Pen|with\s+Keyboard|TG\s+Clear\s+Tint|Clear\s+Tint|Standard\s+Edition)$/iu', '', $clean);

            // Audio features (ANC, Bluetooth, etc.) and color combinations
            $clean = preg_replace('/\s+(?:ANC|Active\s+Noise\s+Cancelling|Bluetooth|Black\/Red)$/iu', '', $clean);

            // Wi-Fi on mobile/tablets when preceded by storage capacity (e.g. 128GB Wi-Fi)
            $clean = preg_replace('/(\d+GB)\s+(?:Wi-Fi|WiFi)$/iu', '$1', $clean);

            // Compound mobile/hardware colors at end
            $clean = preg_replace('/\s+(?:Desert\s+Titanium|Titanium\s+Gray|Space\s+Gray|Gravity\s+Gray|Alps\s+Snowy|Vintage\s+Green|Silky\s+Black|Epi\s+Green|Fluid\s+Silver|Storm\s+Grey|Natural\s+Titanium|Blue\s+Titanium|Black\s+Titanium|White\s+Titanium|Dark\s+Blue)$/iu', '', $clean);

            // Single-word colors at end (Gold is preserved when preceded by PSU wattage e.g. 850W Gold)
            $clean = preg_replace('/(?<!\d{3}W)\s+Gold$/iu', '', $clean);
            $clean = preg_replace('/\s+(?:Black|White|Silver|Titanium|Midnight|Graphite|Yellow|Obsidian)$/iu', '', $clean);

            // Retail packaging and modularity flags at end
            $clean = preg_replace('/\s+(?:OEM|BOX|Modular|ATX\s+3\.0)$/iu', '', $clean);

            if ($clean === $prev) {
                break;
            }
        }

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
     * which is required for Wildberries, Ozon, Joom (path segment), and modern single-page applications.
     */
    public static function buildStoreUrl(string $urlTemplate, string $query): string
    {
        return str_replace('{q}', rawurlencode($query), $urlTemplate);
    }

    /**
     * Builds all store search URLs for a given search query using configured shop templates.
     *
     * @param string $query Clean model search query
     * @param array<string, array>|null $shopsConfig Optional shops configuration
     * @return array<string, string> Map of shopId => canonical store search URL
     */
    public static function buildAllStoreUrls(string $query, ?array $shopsConfig = null): array
    {
        if ($shopsConfig === null) {
            $shopsConfig = Config::get('shops', []);
        }

        $urls = [];
        foreach ($shopsConfig as $shopId => $shop) {
            if (!empty($shop['search_url_template'])) {
                $urls[$shopId] = self::buildStoreUrl($shop['search_url_template'], $query);
            }
        }

        return $urls;
    }
}
