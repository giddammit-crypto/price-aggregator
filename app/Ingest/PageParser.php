<?php
declare(strict_types=1);

namespace App\Ingest;

use DOMDocument;
use DOMXPath;

class PageParser
{
    /**
     * Parses HTML markup to extract product info via JSON-LD or meta tags.
     *
     * @param string $html
     * @param string $url
     * @return array{name: ?string, price: ?float, currency: string, image: ?string, in_stock: bool, sku: ?string, ean: ?string}
     */
    public function parseProductHtml(string $html, string $url = ''): array
    {
        $result = [
            'name' => null,
            'price' => null,
            'currency' => 'RUB',
            'image' => null,
            'in_stock' => true,
            'sku' => null,
            'ean' => null,
        ];

        // 1. Try JSON-LD parsing
        if (preg_match_all('/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $json = @json_decode(trim($jsonStr), true);
                if (!$json) {
                    continue;
                }

                $items = isset($json['@graph']) && is_array($json['@graph']) ? $json['@graph'] : [$json];
                foreach ($items as $item) {
                    $type = (string)($item['@type'] ?? '');
                    if (strcasecmp($type, 'Product') === 0) {
                        $result['name'] = $item['name'] ?? null;
                        $result['sku'] = $item['sku'] ?? null;
                        $result['ean'] = $item['gtin13'] ?? $item['gtin'] ?? null;

                        if (isset($item['image'])) {
                            $result['image'] = is_array($item['image']) ? ($item['image'][0] ?? null) : $item['image'];
                        }

                        if (isset($item['offers'])) {
                            $offers = is_array($item['offers']) && !isset($item['offers']['@type']) ? $item['offers'] : [$item['offers']];
                            foreach ($offers as $off) {
                                if (isset($off['price'])) {
                                    $result['price'] = (float)$off['price'];
                                    $result['currency'] = strtoupper((string)($off['priceCurrency'] ?? 'RUB'));
                                    if (isset($off['availability'])) {
                                        $result['in_stock'] = !str_contains((string)$off['availability'], 'OutOfStock');
                                    }
                                    break;
                                } elseif (isset($off['lowPrice'])) {
                                    $result['price'] = (float)$off['lowPrice'];
                                    $result['currency'] = strtoupper((string)($off['priceCurrency'] ?? 'RUB'));
                                    break;
                                }
                            }
                        }

                        if ($result['name'] && $result['price']) {
                            return $result;
                        }
                    }
                }
            }
        }

        // 2. OpenGraph fallback
        if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $result['name'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (preg_match('/<meta\s+property=["\']product:price:amount["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $result['price'] = (float)$m[1];
        } elseif (preg_match('/<meta\s+property=["\']og:price:amount["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $result['price'] = (float)$m[1];
        }
        if (preg_match('/<meta\s+property=["\']product:price:currency["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $result['currency'] = strtoupper($m[1]);
        }
        if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $result['image'] = $m[1];
        }

        return $result;
    }
}
