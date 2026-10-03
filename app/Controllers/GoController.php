<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Config;
use App\Storage\Pack;
use App\Storage\Repositories\FileClickLog;

class GoController
{
    private FileClickLog $clickLog;

    public function __construct()
    {
        $this->clickLog = new FileClickLog();
    }

    public function redirectOffer(Request $request, array $params): Response
    {
        $productId = (int)($params['productId'] ?? 0);
        $offerKey = urldecode($params['offerKey'] ?? '');

        $product = Pack::get($productId);
        if (!$product) {
            return Response::redirect('/', 302);
        }

        // Find offer
        $targetOffer = null;
        foreach ($product['offers'] ?? [] as $o) {
            if ($o['k'] === $offerKey) {
                $targetOffer = $o;
                break;
            }
        }

        if (!$targetOffer) {
            return Response::redirect("/p/{$product['slug']}-{$productId}", 302);
        }

        $shopId = $targetOffer['shop'];
        $shops = Config::get('shops', []);
        $shop = $shops[$shopId] ?? null;

        // Build target affiliate URL
        if (!empty($targetOffer['url'])) {
            $targetUrl = $targetOffer['url'];
        } else {
            $searchUrl = str_replace('{q}', urlencode($product['title']), $shop['search_url_template'] ?? 'https://www.google.com/search?q=' . urlencode($product['title']));
            $targetUrl = $searchUrl;

            if (!empty($shop['affiliate_template'])) {
                $targetUrl = str_replace('{url}', urlencode($searchUrl), $shop['affiliate_template']);
            }
        }

        if (!self::isSafeTarget($targetUrl)) {
            return Response::redirect("/p/{$product['slug']}-{$productId}");
        }

        $this->clickLog->log(
            $productId,
            $offerKey,
            $shopId,
            $request->getClientIp(),
            $request->getUserAgent(),
            (string)$request->getQuery('sub', '')
        );

        return Response::redirect($targetUrl, 302, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer-when-downgrade'
        ]);
    }

    public function redirectSearch(Request $request, array $params): Response
    {
        $shopId = $params['shopId'] ?? '';
        $query = (string)$request->getQuery('q', '');

        $shops = Config::get('shops', []);
        $shop = $shops[$shopId] ?? null;

        if (!$shop) {
            return Response::redirect('/', 302);
        }

        $targetUrl = str_replace('{q}', urlencode($query), $shop['search_url_template'] ?? 'https://google.com');
        if (!self::isSafeTarget($targetUrl)) {
            return Response::redirect('/');
        }

        $this->clickLog->log(
            0,
            "search:{$shopId}",
            $shopId,
            $request->getClientIp(),
            $request->getUserAgent(),
            'link_only_search'
        );

        return Response::redirect($targetUrl, 302, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer-when-downgrade'
        ]);
    }

    private static function isSafeTarget(string $url): bool
    {
        $parts = parse_url($url);
        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            && !empty($parts['host'])
            && !preg_match('/[\r\n]/', $url);
    }
}
