<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

interface HistoryRepositoryInterface
{
    public function append(int $productId, string $offerKey, float $price, int $stock = 1, ?string $date = null): bool;
    public function getHistory(int $productId, int $days = 90): array;
}
