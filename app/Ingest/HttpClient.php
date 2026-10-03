<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Logger;
use App\Storage\Fs;

class HttpClient
{
    private string $cacheDir;
    private string $rateLimitDir;
    private string $userAgent;
    private int $timeout;

    public function __construct(string $cacheDir = '', string $rateLimitDir = '', int $timeout = 20)
    {
        $root = dirname(__DIR__, 2);
        $this->cacheDir = rtrim($cacheDir ?: ($root . '/data/state/http_cache'), '/') . '/';
        $this->rateLimitDir = rtrim($rateLimitDir ?: ($root . '/data/state/ratelimit'), '/') . '/';
        $this->timeout = $timeout;
        $this->userAgent = 'Mozilla/5.0 (compatible; PriceHubBot/1.0; +https://pricehub.local/bot)';

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
        if (!is_dir($this->rateLimitDir)) {
            @mkdir($this->rateLimitDir, 0775, true);
        }
    }

    public function setRateLimit(string $domain, float $minIntervalSec): void
    {
        $file = $this->rateLimitDir . 'domain_' . md5($domain) . '.txt';
        $now = microtime(true);
        if (file_exists($file)) {
            $last = (float)@file_get_contents($file);
            $wait = ($last + $minIntervalSec) - $now;
            if ($wait > 0 && $wait < 10) {
                usleep((int)($wait * 1_000_000));
            }
        }
        @file_put_contents($file, (string)microtime(true), LOCK_EX);
    }

    /**
     * @param string $url
     * @param array<string, string> $headers
     * @param int $maxRetries
     * @return array{status: int, body: string, headers: array<string, string>, cached: bool, error: ?string}
     */
    public function get(string $url, array $headers = [], int $maxRetries = 2, bool $useCache = true): array
    {
        $domain = parse_url($url, PHP_URL_HOST) ?: 'unknown';
        $cacheMetaFile = $this->cacheDir . md5($url) . '.meta';
        $cacheBodyFile = $this->cacheDir . md5($url) . '.body';

        $reqHeaders = $headers;
        $etag = null;
        $lastModified = null;

        if ($useCache && file_exists($cacheMetaFile) && file_exists($cacheBodyFile)) {
            $meta = @json_decode((string)file_get_contents($cacheMetaFile), true) ?: [];
            if (!empty($meta['etag'])) {
                $reqHeaders['If-None-Match'] = $meta['etag'];
            }
            if (!empty($meta['last_modified'])) {
                $reqHeaders['If-Modified-Since'] = $meta['last_modified'];
            }
        }

        $attempt = 0;
        while ($attempt <= $maxRetries) {
            $this->setRateLimit($domain, 0.5); // Polite default 500ms per domain

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_ENCODING, ''); // supports gzip, deflate
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $formattedHeaders = [];
            foreach ($reqHeaders as $k => $v) {
                $formattedHeaders[] = "{$k}: {$v}";
            }
            if ($formattedHeaders) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
            }

            $raw = curl_exec($ch);
            $err = curl_error($ch);
            $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            if ($err) {
                $attempt++;
                if ($attempt > $maxRetries) {
                    Logger::warning("HTTP request failed for {$url}: {$err}");
                    return ['status' => 0, 'body' => '', 'headers' => [], 'cached' => false, 'error' => $err];
                }
                usleep(500000 * $attempt);
                continue;
            }

            $headerText = substr((string)$raw, 0, $headerSize);
            $body = substr((string)$raw, $headerSize);
            $respHeaders = $this->parseHeaders($headerText);

            // 304 Not Modified -> return cached body
            if ($statusCode === 304 && file_exists($cacheBodyFile)) {
                return [
                    'status' => 200,
                    'body' => (string)file_get_contents($cacheBodyFile),
                    'headers' => $respHeaders,
                    'cached' => true,
                    'error' => null
                ];
            }

            if ($statusCode === 429 || ($statusCode >= 500 && $statusCode <= 504)) {
                $attempt++;
                if ($attempt <= $maxRetries) {
                    usleep((int)(600000 * pow(2, $attempt)));
                    continue;
                }
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                if ($useCache) {
                    $etag = $respHeaders['etag'] ?? null;
                    $lastModified = $respHeaders['last-modified'] ?? null;
                    if ($etag || $lastModified) {
                        Fs::atomicWrite($cacheMetaFile, (string)json_encode([
                            'url' => $url,
                            'etag' => $etag,
                            'last_modified' => $lastModified,
                            'updated_at' => time()
                        ]));
                        Fs::atomicWrite($cacheBodyFile, $body);
                    }
                }
            }

            return [
                'status' => $statusCode,
                'body' => $body,
                'headers' => $respHeaders,
                'cached' => false,
                'error' => null
            ];
        }

        return ['status' => 0, 'body' => '', 'headers' => [], 'cached' => false, 'error' => 'Max retries exceeded'];
    }

    /**
     * @param string $headerText
     * @return array<string, string>
     */
    private function parseHeaders(string $headerText): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerText);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        return $headers;
    }
}
