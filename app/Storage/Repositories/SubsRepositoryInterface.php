<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

interface SubsRepositoryInterface
{
    public function subscribe(string $email, int $productId, float $targetPrice): array;
    public function confirm(string $token): bool;
    public function unsubscribe(string $token): bool;
    public function getActiveForProduct(int $productId): array;
}
