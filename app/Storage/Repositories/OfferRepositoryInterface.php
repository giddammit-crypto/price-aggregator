<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

interface OfferRepositoryInterface
{
    public function getShopShardItems(string $shop, string $shard): array;
    public function saveShopShardItems(string $shop, string $shard, array $items): bool;
    public function getAllShopShards(string $shop): array;
}
