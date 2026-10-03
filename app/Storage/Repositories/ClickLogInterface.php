<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

interface ClickLogInterface
{
    public function log(int $productId, string $offerKey, string $shop, string $clientIp, string $userAgent, string $sub = ''): bool;
    public function getTodayClicks(): int;
}
