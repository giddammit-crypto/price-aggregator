<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

use App\Storage\Snapshot;

class FileCatalogIndex implements CatalogIndexInterface
{
    public function getCategories(): array
    {
        return Snapshot::loadArray('categories.php', []);
    }

    public function getCategoryRows(int $catId): array
    {
        return Snapshot::loadArray("cat/{$catId}.php", []);
    }

    public function getCategoryOrder(int $catId): array
    {
        return Snapshot::loadArray("cat/{$catId}.order.php", []);
    }

    public function getCategoryFacets(int $catId): array
    {
        return Snapshot::loadArray("cat/{$catId}.facets.php", []);
    }

    public function getHomeData(): array
    {
        return Snapshot::loadArray('home.php', [
            'drops' => [],
            'popular' => [],
            'newest' => []
        ]);
    }

    public function getSearchPosting(string $token): array
    {
        if (strlen($token) < 2) {
            return [];
        }
        $prefix = substr(hash('crc32b', $token), 0, 2);
        $file = "search/tok_{$prefix}.php";
        $data = Snapshot::loadArray($file, []);
        return $data[$token] ?? [];
    }

    public function getVocab(): array
    {
        return Snapshot::loadArray('search/vocab.php', []);
    }

    public function getMeta(): array
    {
        return Snapshot::loadArray('meta.php', [
            'total_products' => 0,
            'total_offers' => 0,
            'build_time' => 0
        ]);
    }
}
