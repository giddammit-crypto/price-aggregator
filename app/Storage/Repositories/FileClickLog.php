<?php
declare(strict_types=1);

namespace App\Storage\Repositories;

use App\Storage\Fs;

class FileClickLog implements ClickLogInterface
{
    private string $logDir;

    public function __construct(string $logDir = '')
    {
        $this->logDir = rtrim($logDir ?: (dirname(__DIR__, 3) . '/data/logs/clicks'), '/') . '/';
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0775, true);
        }
    }

    public function log(int $productId, string $offerKey, string $shop, string $clientIp, string $userAgent, string $sub = ''): bool
    {
        $date = date('Y-m-d');
        $file = $this->logDir . "{$date}.ndjson";

        $entry = [
            'ts' => time(),
            'product' => $productId,
            'offer' => $offerKey,
            'shop' => $shop,
            'ip_hash' => hash('xxh128', $clientIp),
            'ua_hash' => hash('xxh128', $userAgent),
            'sub' => $sub
        ];

        return Fs::appendLine($file, json_encode($entry, JSON_UNESCAPED_UNICODE));
    }

    public function getTodayClicks(): int
    {
        $date = date('Y-m-d');
        $file = $this->logDir . "{$date}.ndjson";
        if (!file_exists($file)) {
            return 0;
        }

        $count = 0;
        $fh = @fopen($file, 'rb');
        if ($fh) {
            while (fgets($fh) !== false) {
                $count++;
            }
            fclose($fh);
        }
        return $count;
    }
}
