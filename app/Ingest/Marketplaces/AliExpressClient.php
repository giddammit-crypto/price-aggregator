<?php
declare(strict_types=1);

namespace App\Ingest\Marketplaces;

use App\Core\Config;
use App\Core\Logger;
use App\Ingest\HttpClient;

class AliExpressClient
{
    private string $appKey;
    private string $appSecret;
    private string $trackingId;
    private string $gatewayUrl;
    private HttpClient $http;

    public function __construct(?HttpClient $http = null)
    {
        $secrets = Config::get('secrets', []);
        $this->appKey = (string)($secrets['aliexpress']['app_key'] ?? '');
        $this->appSecret = (string)($secrets['aliexpress']['app_secret'] ?? '');
        $this->trackingId = (string)($secrets['aliexpress']['tracking_id'] ?? 'pricehub');
        $this->gatewayUrl = 'https://api-sg.aliexpress.com/sync';
        $this->http = $http ?: new HttpClient();
    }

    public function isConfigured(): bool
    {
        return !empty($this->appKey) && !empty($this->appSecret);
    }

    /**
     * Signs AliExpress Open API request using MD5 or HMAC-SHA256 according to documentation.
     */
    public function sign(array $params): string
    {
        ksort($params);
        $signStr = $this->appSecret;
        foreach ($params as $k => $v) {
            if ($k !== '' && $v !== null && $v !== '') {
                $signStr .= $k . (string)$v;
            }
        }
        $signStr .= $this->appSecret;
        return strtoupper(md5($signStr));
    }

    /**
     * Search products in AliExpress Affiliate API.
     */
    public function search(string $query, int $page = 1, int $limit = 20): array
    {
        if (!$this->isConfigured()) {
            return [
                'configured' => false,
                'items' => [],
                'fallback_url' => $this->getSearchLink($query)
            ];
        }

        $params = [
            'app_key' => $this->appKey,
            'timestamp' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'v' => '2.0',
            'sign_method' => 'md5',
            'method' => 'aliexpress.affiliate.product.query',
            'keywords' => $query,
            'page_no' => (string)$page,
            'page_size' => (string)$limit,
            'tracking_id' => $this->trackingId,
            'target_currency' => 'RUB',
            'target_language' => 'RU',
            'ship_to_country' => 'RU',
            'sort' => 'SALE_PRICE_ASC'
        ];

        $params['sign'] = $this->sign($params);
        $url = $this->gatewayUrl . '?' . http_build_query($params);
        $res = $this->http->get($url, [], 2, true);

        if ($res['status'] !== 200 || empty($res['body'])) {
            Logger::warning("AliExpress API request failed: status {$res['status']}");
            return ['configured' => true, 'items' => [], 'fallback_url' => $this->getSearchLink($query)];
        }

        $json = @json_decode($res['body'], true);
        $products = $json['aliexpress_affiliate_product_query_response']['resp_result']['result']['products']['product'] ?? [];

        $items = [];
        foreach ($products as $p) {
            $items[] = [
                'external_id' => (string)($p['product_id'] ?? ''),
                'title' => (string)($p['product_title'] ?? ''),
                'price' => (float)($p['target_sale_price'] ?? $p['sale_price'] ?? 0),
                'old_price' => isset($p['target_original_price']) ? (float)$p['target_original_price'] : null,
                'url' => (string)($p['promotion_link'] ?? $p['product_detail_url'] ?? ''),
                'image_url' => (string)($p['product_main_image_url'] ?? ''),
                'delivery_days' => 18,
                'in_stock' => true,
                'seller_rating' => (float)($p['evaluate_rate'] ?? 4.5),
            ];
        }

        return [
            'configured' => true,
            'items' => $items,
            'fallback_url' => $this->getSearchLink($query)
        ];
    }

    /**
     * Fallback search link with partner tracking
     */
    public function getSearchLink(string $query): string
    {
        $encoded = urlencode($query);
        return "https://aliexpress.ru/wholesale?SearchText={$encoded}&tracking_id={$this->trackingId}";
    }
}
