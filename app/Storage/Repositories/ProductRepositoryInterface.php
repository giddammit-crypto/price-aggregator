<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findMultiple(array $ids): array;
    public function savePack(string $shard, array $products): bool;
    public function getShardProducts(string $shard): array;
}
