<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

use App\Storage\Pack;

class FileProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?array
    {
        return Pack::get($id);
    }

    public function findMultiple(array $ids): array
    {
        return Pack::getMultiple($ids);
    }

    public function savePack(string $shard, array $products): bool
    {
        return Pack::writePack($shard, $products);
    }

    public function getShardProducts(string $shard): array
    {
        return Pack::getAllFromShard($shard);
    }
}
