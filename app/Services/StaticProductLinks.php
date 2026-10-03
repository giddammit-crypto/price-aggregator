<?php
declare(strict_types=1);

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Product routes included in a bounded GitHub Pages export. */
final class StaticProductLinks
{
    public static function productPath(string $href, string $prefix = '/price-aggregator'): ?string
    {
        $url = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (parse_url($url, PHP_URL_HOST) !== null) {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return null;
        }
        if (str_starts_with($path, $prefix . '/p/')) {
            $path = substr($path, strlen($prefix));
        }
        return str_starts_with($path, '/p/') ? rtrim($path, '/') . '/' : null;
    }

    /** Remove a whole unavailable card; non-card navigation links disappear. */
    public static function prune(string $html, array $paths, string $prefix = '/price-aggregator'): string
    {
        $isMissing = static function (string $fragment) use ($paths, $prefix): bool {
            if (!preg_match_all('~\bhref\s*=\s*(["\'])(.*?)\1~is', $fragment, $links, PREG_SET_ORDER)) {
                return false;
            }
            foreach ($links as $link) {
                $path = self::productPath($link[2], $prefix);
                if ($path !== null && !isset($paths[$path])) {
                    return true;
                }
            }
            return false;
        };

        $html = preg_replace_callback('~<article\b(?=[^>]*\bclass\s*=\s*["\'][^"\']*\bproduct-card\b)[^>]*>.*?</article>~is',
            static fn(array $match): string => $isMissing($match[0]) ? '' : $match[0], $html);
        if ($html === null) {
            throw new \RuntimeException('Could not filter exported product cards');
        }
        $html = preg_replace_callback('~<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>.*?</a>~is',
            static fn(array $match): string => $isMissing($match[0]) ? '' : $match[0], $html);
        if ($html === null) {
            throw new \RuntimeException('Could not filter exported product links');
        }
        return $html;
    }

    /** Scan the actual built artifact, not only the snapshot rows or search JSON. */
    public static function check(string $directory, string $prefix = '/price-aggregator'): array
    {
        $errors = [];
        $pages = 0;
        $links = 0;
        $productPages = 0;
        $referenced = [];
        if (!is_file($directory . '/index.html') || !is_dir($directory . '/p')) {
            return ['pages' => 0, 'product_pages' => 0, 'links' => 0, 'errors' => ['Missing home or product directory']];
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'html') {
                continue;
            }
            $pages++;
            $relative = substr($file->getPathname(), strlen(rtrim($directory, '/')) + 1);
            $html = file_get_contents($file->getPathname());
            if ($html === false) {
                $errors[] = "Unreadable page: {$relative}";
                continue;
            }
            if (str_starts_with($relative, 'p/') && str_ends_with($relative, '/index.html')) {
                $productPages++;
                $route = substr($relative, 0, -strlen('/index.html'));
                if (!str_contains($html, '"@type": "Product"') || !str_contains($html, '/'. $route . '"')) {
                    $errors[] = "Not a concrete product page: {$relative}";
                }
            }
            preg_match_all('~\bhref\s*=\s*(["\'])(.*?)\1~is', $html, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $href = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $localPath = parse_url($href, PHP_URL_PATH);
                if (is_string($localPath) && str_starts_with($localPath, $prefix . '/')
                    && self::productPath($href, $prefix) === null) {
                    $relativePath = rawurldecode(substr($localPath, strlen($prefix) + 1));
                    if (str_contains($relativePath, '..') || (!is_file($directory . '/' . $relativePath)
                        && !is_file($directory . '/' . rtrim($relativePath, '/') . '/index.html'))) {
                        $errors[] = "Broken local link in {$relative}: {$href}";
                    }
                }
                $path = self::productPath($match[2], $prefix);
                if ($path === null) {
                    continue;
                }
                $links++;
                $referenced[$path] = true;
                if (!str_starts_with($match[2], $prefix . '/p/')
                    || !preg_match('~^/p/[a-z0-9-]+-[1-9][0-9]*/$~', $path)
                    || !is_file($directory . $path . 'index.html')) {
                    $errors[] = "Broken product link in {$relative}: {$match[2]}";
                }
            }
        }

        foreach (['search_index.json', 'suggest.json'] as $indexName) {
            $index = json_decode((string)@file_get_contents($directory . '/api/' . $indexName), true);
            if (!is_array($index)) {
                $errors[] = "Missing or invalid {$indexName}";
                continue;
            }
            foreach ($index as $row) {
                $path = self::productPath((string)($row['url'] ?? ''), $prefix);
                if ($path === null || !preg_match('~^/p/[a-z0-9-]+-[1-9][0-9]*/$~', $path)
                    || !is_file($directory . $path . 'index.html')) {
                    $errors[] = "{$indexName} has no product page for ID " . ($row['id'] ?? '?');
                }
            }
        }

        return ['pages' => $pages, 'product_pages' => $productPages, 'links' => $links,
            'unique_product_links' => count($referenced), 'errors' => $errors];
    }
}
