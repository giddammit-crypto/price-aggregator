<?php
declare(strict_types=1);

namespace App\Services;

use App\Storage\Fs;
use App\Storage\Pack;
use App\Storage\Snapshot;

/**
 * Service: SearchIndexGenerator
 * Compiles client-side search and suggestion indices with complete metadata (prices, drops, sellers, brands).
 */
class SearchIndexGenerator
{
    /**
     * Build the search index and suggest index
     *
     * @param string $basePrefix E.g. '' or '/price-aggregator'
     * @return array{search: array, suggest: array, exportedProductIds: int[]}
     */
    public static function build(string $basePrefix = ''): array
    {
        $categories = Snapshot::loadArray('categories.php', []);
        $productIdsToExport = [];

        // A. Home page products
        $homeData = Snapshot::loadArray('home.php', []);
        foreach (['drops', 'popular', 'newest'] as $key) {
            foreach ($homeData[$key] ?? [] as $item) {
                if (!empty($item['id'])) {
                    $productIdsToExport[(int)$item['id']] = true;
                }
            }
        }
        if (!empty($homeData['featured_drop']['id'])) {
            $productIdsToExport[(int)$homeData['featured_drop']['id']] = true;
        }
        foreach (VerifiedProductImage::approvedIds() as $id) {
            $productIdsToExport[(int)$id] = true;
        }

        // B. Category items (first 36 items + top ordered)
        foreach ($categories as $cat) {
            $catId = $cat['id'];
            $catRows = Snapshot::loadArray("cat/{$catId}.php", []);
            foreach (array_slice($catRows, 0, 36) as $row) {
                if (!empty($row['id'])) {
                    $productIdsToExport[(int)$row['id']] = true;
                }
            }
            $orderFile = "cat/{$catId}.order.php";
            $orderData = Snapshot::loadArray($orderFile, []);
            $orderedIds = $orderData['popular'] ?? $orderData['price_asc'] ?? [];
            foreach (array_slice($orderedIds, 0, 20) as $pid) {
                $productIdsToExport[(int)$pid] = true;
            }
        }

        // C. Expand with neighbor products and similar models
        $expandedIds = $productIdsToExport;
        foreach (array_keys($productIdsToExport) as $pid) {
            $p = Pack::get((int)$pid);
            if (!$p) continue;
            $cRows = Snapshot::loadArray("cat/{$p['cat']}.php", []);
            $cIdx = -1;
            foreach ($cRows as $idx => $r) {
                if ($r['id'] === (int)$pid) { $cIdx = $idx; break; }
            }
            if ($cIdx > 0 && isset($cRows[$cIdx - 1]['id'])) {
                $expandedIds[(int)$cRows[$cIdx - 1]['id']] = true;
            }
            if ($cIdx >= 0 && isset($cRows[$cIdx + 1]['id'])) {
                $expandedIds[(int)$cRows[$cIdx + 1]['id']] = true;
            }
            $simCount = 0;
            foreach ($cRows as $r) {
                if ($r['id'] !== (int)$pid) {
                    $expandedIds[(int)$r['id']] = true;
                    $simCount++;
                    if ($simCount >= 4) break;
                }
            }
        }

        $searchIndex = [];
        $suggestIndex = [];

        foreach (array_keys($expandedIds) as $pid) {
            $p = Pack::get((int)$pid);
            if (!$p || empty($p['pub'])) continue;

            $slug = $p['slug'] ?? ('product-' . $pid);
            if (!preg_match('/^[a-z0-9-]+$/', (string)$slug)) {
                continue;
            }

            $catName = $categories[$p['cat']]['name'] ?? 'Каталог';
            $minPrice = (int)($p['agg']['min'] ?? 0);
            $offersCnt = (int)($p['agg']['cnt'] ?? count($p['offers'] ?? []));
            $drop = (float)($p['agg']['drop'] ?? 0.0);
            $isMp = !empty($p['agg']['mp']) || !empty($p['agg']['has_mp']);
            $isCb = !empty($p['agg']['cb']) || !empty($p['agg']['has_cb']);
            $seller = $isCb ? 'crossborder' : ($isMp ? 'marketplace' : 'retail');
            $pop = (int)($p['popularity'] ?? ($offersCnt * 10));
            $verifiedImg = VerifiedProductImage::forProduct($p);
            $img = $verifiedImg ?? $p['img'] ?? '/assets/img/placeholder.svg';

            $searchIndex[] = [
                'id' => (int)$pid,
                'title' => (string)$p['title'],
                'brand' => (string)($p['brand'] ?? ''),
                'cat' => (string)$catName,
                'price' => $minPrice,
                'offers' => $offersCnt,
                'drop' => $drop,
                'seller' => $seller,
                'mp' => $isMp ? 1 : 0,
                'cb' => $isCb ? 1 : 0,
                'pop' => $pop,
                'url' => "{$basePrefix}/p/{$slug}-{$pid}/",
                'image' => "{$basePrefix}{$img}",
                'specs' => $p['specs'] ?? [],
                'attrs' => $p['attrs'] ?? []
            ];

            $suggestIndex[] = [
                'title' => (string)$p['title'],
                'brand' => (string)($p['brand'] ?? ''),
                'price' => $minPrice,
                'url' => "{$basePrefix}/p/{$slug}-{$pid}/"
            ];
        }

        return [
            'search' => $searchIndex,
            'suggest' => $suggestIndex,
            'exportedProductIds' => array_keys($expandedIds)
        ];
    }

    /**
     * Atomically write search_index.json and suggest.json to the target directory
     */
    public static function write(string $targetDir, string $basePrefix = ''): array
    {
        @mkdir($targetDir, 0775, true);
        $data = self::build($basePrefix);
        $searchJson = json_encode($data['search'], JSON_UNESCAPED_UNICODE);
        $suggestJson = json_encode(array_slice($data['suggest'], 0, 100), JSON_UNESCAPED_UNICODE);
        Fs::atomicWrite($targetDir . '/search_index.json', $searchJson);
        Fs::atomicWrite($targetDir . '/suggest.json', $suggestJson);
        return $data;
    }
}
