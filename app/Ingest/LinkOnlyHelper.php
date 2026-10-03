<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Config;

class LinkOnlyHelper
{
    /**
     * Builds link_only search URL for a given shop and query.
     */
    public static function buildSearchUrl(string $shopId, string $query, int $productId = 0): string
    {
        $shops = Config::get('shops', []);
        $shop = $shops[$shopId] ?? null;

        if (!$shop) {
            return 'https://yandex.ru/search/?text=' . urlencode($query);
        }

        $template = $shop['search_url_template'] ?? '';
        if (!$template) {
            return 'https://yandex.ru/search/?text=' . urlencode($shop['name'] . ' ' . $query);
        }

        $url = str_replace('{q}', urlencode($query), $template);

        // Apply affiliate template if configured
        if (!empty($shop['affiliate_template'])) {
            $affTemplate = $shop['affiliate_template'];
            $affUrl = str_replace('{url}', urlencode($url), $affTemplate);
            if (str_contains($affTemplate, '{sub}')) {
                $affUrl = str_replace('{sub}', 'p' . $productId, $affUrl);
            }
            return $affUrl;
        }

        // Add UTM tags
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . 'utm_source=pricehub&utm_medium=referral&utm_campaign=product_' . $productId;
    }

    /**
     * Builds direct offer outbound URL with affiliate wrap and sub-ids.
     */
    public static function buildOfferUrl(string $shopId, string $rawUrl, int $productId = 0, string $sub = ''): string
    {
        $shops = Config::get('shops', []);
        $shop = $shops[$shopId] ?? null;

        if (!$shop || empty($shop['affiliate_template'])) {
            $sep = str_contains($rawUrl, '?') ? '&' : '?';
            return $rawUrl . $sep . 'utm_source=pricehub&utm_medium=cpc&utm_campaign=product_' . $productId;
        }

        $template = $shop['affiliate_template'];
        $url = str_replace('{url}', urlencode($rawUrl), $template);
        if (str_contains($template, '{sub}')) {
            $url = str_replace('{sub}', $sub ?: ('p' . $productId), $url);
        }

        return $url;
    }
}
