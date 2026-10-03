<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

use App\Storage\Fs;

class FileHistoryRepository implements HistoryRepositoryInterface
{
    private string $baseDir;

    public function __construct(string $baseDir = '')
    {
        $this->baseDir = rtrim($baseDir ?: (dirname(__DIR__, 3) . '/data/history'), '/') . '/';
    }

    private function getLogFile(int $productId): string
    {
        $shard = sprintf('h%03d', $productId % 256);
        return $this->baseDir . "{$shard}/{$productId}.log";
    }

    public function append(int $productId, string $offerKey, float $price, int $stock = 1, ?string $date = null): bool
    {
        $file = $this->getLogFile($productId);
        $date = $date ?: date('Y-m-d');
        $line = "{$date}|{$offerKey}|{$price}|{$stock}";
        return Fs::appendLine($file, $line);
    }

    public function getHistory(int $productId, int $days = 90): array
    {
        $file = $this->getLogFile($productId);
        if (!file_exists($file)) {
            return [];
        }

        $cutoff = date('Y-m-d', strtotime("-{$days} days"));
        $byDate = [];

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 3) {
                continue;
            }
            [$date, $key, $price] = $parts;
            if ($date < $cutoff) {
                continue;
            }
            $p = (float)$price;
            if (!isset($byDate[$date]) || $p < $byDate[$date]['min']) {
                $byDate[$date] = [
                    'date' => $date,
                    'min' => $p,
                    'offer' => $key
                ];
            }
        }

        ksort($byDate);
        return array_values($byDate);
    }
}
