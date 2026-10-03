<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

interface CatalogIndexInterface
{
    public function getCategories(): array;
    public function getCategoryRows(int $catId): array;
    public function getCategoryOrder(int $catId): array;
    public function getCategoryFacets(int $catId): array;
    public function getHomeData(): array;
    public function getSearchPosting(string $token): array;
    public function getVocab(): array;
    public function getMeta(): array;
}
