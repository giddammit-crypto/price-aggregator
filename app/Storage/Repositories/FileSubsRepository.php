<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

use App\Storage\Fs;

class FileSubsRepository implements SubsRepositoryInterface
{
    private string $baseDir;

    public function __construct(string $baseDir = '')
    {
        $this->baseDir = rtrim($baseDir ?: (dirname(__DIR__, 3) . '/data/subs'), '/') . '/';
        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0775, true);
        }
    }

    private function getFile(int $productId): string
    {
        $shard = sprintf('p%03d', $productId % 256);
        return $this->baseDir . "{$shard}.ndjson";
    }

    public function subscribe(string $email, int $productId, float $targetPrice): array
    {
        $email = strtolower(trim($email));
        $emailHash = hash('sha256', $email);
        $token = bin2hex(random_bytes(16));

        $record = [
            'email_hash' => $emailHash,
            'email' => $email,
            'product_id' => $productId,
            'target_price' => $targetPrice,
            'confirmed' => 0,
            'token' => $token,
            'created' => date('Y-m-d H:i:s'),
            'last_notified' => null
        ];

        $file = $this->getFile($productId);
        Fs::appendLine($file, json_encode($record, JSON_UNESCAPED_UNICODE));

        return $record;
    }

    public function confirm(string $token): bool
    {
        // Scan shards to find and confirm subscription
        $files = glob($this->baseDir . 'p*.ndjson') ?: [];
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $updated = false;
            $newRows = [];

            foreach ($lines as $line) {
                $row = json_decode($line, true);
                if (is_array($row) && isset($row['token']) && $row['token'] === $token) {
                    $row['confirmed'] = 1;
                    $updated = true;
                }
                if ($row) {
                    $newRows[] = json_encode($row, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($updated) {
                Fs::atomicWrite($file, implode("\n", $newRows) . "\n");
                return true;
            }
        }
        return false;
    }

    public function unsubscribe(string $token): bool
    {
        $files = glob($this->baseDir . 'p*.ndjson') ?: [];
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $updated = false;
            $newRows = [];

            foreach ($lines as $line) {
                $row = json_decode($line, true);
                if (is_array($row) && isset($row['token']) && $row['token'] === $token) {
                    $updated = true;
                    continue; // remove
                }
                if ($row) {
                    $newRows[] = json_encode($row, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($updated) {
                Fs::atomicWrite($file, implode("\n", $newRows) . "\n");
                return true;
            }
        }
        return false;
    }

    public function getActiveForProduct(int $productId): array
    {
        $file = $this->getFile($productId);
        if (!file_exists($file)) {
            return [];
        }

        $active = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $row = json_decode($line, true);
            if (is_array($row) && !empty($row['confirmed']) && ($row['product_id'] ?? 0) === $productId) {
                $active[] = $row;
            }
        }
        return $active;
    }

    public function getAllActive(int $limit = 1000): array
    {
        $files = glob($this->baseDir . 'p*.ndjson') ?: [];
        $active = [];
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $row = json_decode($line, true);
                if (is_array($row) && !empty($row['confirmed'])) {
                    $active[] = $row;
                    if (count($active) >= $limit) {
                        return $active;
                    }
                }
            }
        }
        return $active;
    }
}
