<?php
/**
 * Service: BuildIndexService
 * Compiles catalog products into high-speed OPcache snapshots and atomically publishes them.
 */

declare(strict_types=1);

namespace App\Services;

use App\Storage\Fs;
use App\Storage\Pack;
use App\Storage\Snapshot;
use App\Core\Utf8;

class BuildIndexService
{
    private string $root;
    private string $productsDir;
    private string $snapshotsDir;
    private array $categories;
    private array $synonyms;

    public function __construct(string $root)
    {
        $this->root = $root;
        $this->productsDir = $root . '/data/catalog/products';
        $this->snapshotsDir = $root . '/data/snapshots';
        $this->categories = require $root . '/config/categories.php';
        $this->synonyms = require $root . '/config/synonyms.php';
    }

    public function run(): string
    {
        $startTime = microtime(true);
        $snapshotId = date('YmdHis');
        $buildDir = $this->snapshotsDir . '/.build-' . $snapshotId;
        @mkdir($buildDir, 0775, true);
        @mkdir($buildDir . '/cat', 0775, true);
        @mkdir($buildDir . '/search', 0775, true);
        @mkdir($buildDir . '/sitemap', 0775, true);

        $totalProducts = 0;
        $totalOffers = 0;
        $catProducts = [];
        $catFacets = [];
        $allBrands = [];
        $searchPostings = [];
        $vocab = [];
        $homeDrops = [];
        $homePopular = [];
        $homeNewest = [];
        $sitemapUrls = [];

        Pack::init($this->productsDir);

        for ($shardNum = 0; $shardNum < 256; $shardNum++) {
            $shardName = sprintf('p%03d', $shardNum);
            $products = Pack::getAllFromShard($shardName);

            foreach ($products as $p) {
                if (empty($p['pub'])) {
                    continue;
                }

                $id = (int)$p['id'];
                $catId = (int)$p['cat'];
                $brand = (string)($p['brand'] ?? 'Разное');
                $title = (string)$p['title'];
                $slug = (string)($p['slug'] ?? Utf8::slugify($title));
                $img = (string)($p['img'] ?? '/assets/img/placeholder.svg');
                $agg = $p['agg'] ?? [];
                $minPrice = (int)($agg['min'] ?? 0);
                $cnt = (int)($agg['cnt'] ?? 0);
                $drop = (float)($agg['drop'] ?? 0.0);
                $pop = (int)($p['popularity'] ?? ($cnt * 10 + rand(1, 50)));
                $attrs = $p['attrs'] ?? [];

                $totalProducts++;
                $totalOffers += $cnt;

                $allBrands[$brand] = ($allBrands[$brand] ?? 0) + 1;

                $row = [
                    'id' => $id,
                    'b' => $brand,
                    'p' => $minPrice,
                    'c' => $cnt,
                    'd' => $drop,
                    'pop' => $pop,
                    't' => $title,
                    'img' => $img,
                    'slug' => $slug,
                    'attrs' => $attrs,
                    'mp' => $agg['mp'] ?? 0,
                    'cb' => $agg['cb'] ?? 0
                ];

                $catProducts[$catId][] = $row;

                $parentId = $this->categories[$catId]['parent_id'] ?? null;
                if ($parentId !== null) {
                    $catProducts[$parentId][] = $row;
                }

                // Facets
                if (!isset($catFacets[$catId]['brand'][$brand])) {
                    $catFacets[$catId]['brand'][$brand] = 0;
                }
                $catFacets[$catId]['brand'][$brand]++;

                if ($parentId !== null) {
                    if (!isset($catFacets[$parentId]['brand'][$brand])) {
                        $catFacets[$parentId]['brand'][$brand] = 0;
                    }
                    $catFacets[$parentId]['brand'][$brand]++;
                }

                foreach ($attrs as $attrKey => $attrVal) {
                    if (is_scalar($attrVal)) {
                        $vStr = (string)$attrVal;
                        if (!isset($catFacets[$catId][$attrKey][$vStr])) {
                            $catFacets[$catId][$attrKey][$vStr] = 0;
                        }
                        $catFacets[$catId][$attrKey][$vStr]++;

                        if ($parentId !== null) {
                            if (!isset($catFacets[$parentId][$attrKey][$vStr])) {
                                $catFacets[$parentId][$attrKey][$vStr] = 0;
                            }
                            $catFacets[$parentId][$attrKey][$vStr]++;
                        }
                    }
                }

                // Search token indexing
                $textToIndex = $title . ' ' . $brand . ' ' . ($p['model'] ?? '') . ' ' . ($p['mpn'] ?? '');
                $tokens = $this->tokenize($textToIndex);
                foreach ($tokens as $tok => $weight) {
                    // Only index tokens with at least 2 chars and cap posting size
                    if (!isset($searchPostings[$tok])) {
                        $searchPostings[$tok] = [];
                    }
                    if (count($searchPostings[$tok]) < 100) {
                        $searchPostings[$tok][] = [$id, $weight, $pop];
                    }
                    $vocab[$tok] = ($vocab[$tok] ?? 0) + 1;
                }

                // Home page blocks
                if ($drop >= 8.0 && count($homeDrops) < 24) {
                    $homeDrops[] = $row;
                }
                if (count($homePopular) < 24) {
                    $homePopular[] = $row;
                }
                if (count($homeNewest) < 24) {
                    $homeNewest[] = $row;
                }

                // Sitemap URL
                if (count($sitemapUrls) < 45000) {
                    $sitemapUrls[] = "/p/{$slug}-{$id}";
                }
            }
        }

        // 2. Compile category files and pre-computed sort orders
        foreach ($this->categories as $cId => $cData) {
            $rows = $catProducts[$cId] ?? [];

            usort($rows, fn($a, $b) => $b['pop'] <=> $a['pop']);
            Fs::atomicWritePhpArray($buildDir . "/cat/{$cId}.php", $rows);

            $orderAsc = $rows;
            usort($orderAsc, fn($a, $b) => $a['p'] <=> $b['p']);
            $orderAscIds = array_column($orderAsc, 'id');

            $orderDesc = $rows;
            usort($orderDesc, fn($a, $b) => $b['p'] <=> $a['p']);
            $orderDescIds = array_column($orderDesc, 'id');

            $orderDrop = $rows;
            usort($orderDrop, fn($a, $b) => $b['d'] <=> $a['d']);
            $orderDropIds = array_column($orderDrop, 'id');

            $orderNew = $rows;
            usort($orderNew, fn($a, $b) => $b['id'] <=> $a['id']);
            $orderNewIds = array_column($orderNew, 'id');

            $orders = [
                'price_asc' => $orderAscIds,
                'price_desc' => $orderDescIds,
                'drop' => $orderDropIds,
                'new' => $orderNewIds
            ];
            Fs::atomicWritePhpArray($buildDir . "/cat/{$cId}.order.php", $orders);

            $facets = $catFacets[$cId] ?? [];
            Fs::atomicWritePhpArray($buildDir . "/cat/{$cId}.facets.php", $facets);
        }

        // 3. Categories tree
        $categoriesTree = $this->categories;
        foreach ($categoriesTree as $cId => &$cData) {
            $cData['count'] = count($catProducts[$cId] ?? []);
        }
        unset($cData);
        Fs::atomicWritePhpArray($buildDir . '/categories.php', $categoriesTree);

        // 4. Brands
        arsort($allBrands);
        Fs::atomicWritePhpArray($buildDir . '/brands.php', $allBrands);

        // 5. Home blocks
        usort($homeDrops, fn($a, $b) => $b['d'] <=> $a['d']);
        usort($homePopular, fn($a, $b) => $b['pop'] <=> $a['pop']);
        usort($homeNewest, fn($a, $b) => $b['id'] <=> $a['id']);

        $homeData = [
            'drops' => array_slice($homeDrops, 0, 16),
            'popular' => array_slice($homePopular, 0, 16),
            'newest' => array_slice($homeNewest, 0, 16)
        ];
        Fs::atomicWritePhpArray($buildDir . '/home.php', $homeData);

        // 6. Search Postings sharded by crc32
        $shardedPostings = [];
        foreach ($searchPostings as $tok => $postings) {
            $tokStr = (string)$tok;
            $prefix = substr(hash('crc32b', $tokStr), 0, 2);
            $shardedPostings[$prefix][$tokStr] = $postings;
        }

        foreach ($shardedPostings as $prefix => $data) {
            Fs::atomicWritePhpArray($buildDir . "/search/tok_{$prefix}.php", $data);
        }

        // Top Vocab for autocomplete
        arsort($vocab);
        $topVocab = array_slice(array_keys($vocab), 0, 5000);
        sort($topVocab);
        Fs::atomicWritePhpArray($buildDir . '/search/vocab.php', $topVocab);

        // 7. Sitemap
        Fs::atomicWritePhpArray($buildDir . '/sitemap/part_1.php', $sitemapUrls);

        // 8. Meta
        $duration = round(microtime(true) - $startTime, 2);
        $meta = [
            'id' => $snapshotId,
            'total_products' => $totalProducts,
            'total_offers' => $totalOffers,
            'build_time' => $duration,
            'created_at' => date('Y-m-d H:i:s')
        ];
        Fs::atomicWritePhpArray($buildDir . '/meta.php', $meta);

        // 9. Atomic Publish via rename and CURRENT update
        $finalSnapshotDir = $this->snapshotsDir . '/' . $snapshotId;
        rename($buildDir, $finalSnapshotDir);
        Snapshot::init($this->snapshotsDir);
        Snapshot::publish($snapshotId);
        Snapshot::cleanupOld(2);

        return "Snapshot {$snapshotId} successfully built in {$duration}s ({$totalProducts} products, {$totalOffers} offers)";
    }

    private function tokenize(string $text): array
    {
        $lower = Utf8::strtolower(str_replace('ё', 'е', $text));
        preg_match_all('/[a-zа-я0-9]+/u', $lower, $m);
        $rawTokens = $m[0] ?? [];

        $tokens = [];
        foreach ($rawTokens as $tok) {
            if (strlen($tok) < 2) {
                continue;
            }
            $tokens[$tok] = 5;
            if (isset($this->synonyms[$tok])) {
                $syn = $this->synonyms[$tok];
                $tokens[$syn] = 4;
            }
            if (preg_match('/^([a-zа-я]+)([0-9]+)$/u', $tok, $splitM)) {
                $tokens[$splitM[1]] = 3;
                $tokens[$splitM[2]] = 4;
            }
        }
        return $tokens;
    }
}
