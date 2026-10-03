<?php
declare(strict_types=1);

/**
 * Pure PHP Asset Minifier (No Node / Vite / NPM required)
 * Builds production-ready minified CSS with stripped comments and whitespace.
 */

$root = dirname(__DIR__);
$srcCss = $root . '/public/assets/css/style.css';
$dstCss = $root . '/public/assets/css/style.min.css';

if (!file_exists($srcCss)) {
    echo "[ERROR] CSS file not found: {$srcCss}\n";
    exit(1);
}

$css = file_get_contents($srcCss);
$originalSize = strlen($css);

// 1. Remove comments
$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
// 2. Remove space after colons and around braces
$css = preg_replace('/\s*([\{\};:,>])\s*/', '$1', (string)$css);
// 3. Remove trailing semicolons before closing brace
$css = str_replace(';}', '}', (string)$css);
// 4. Collapse multiple spaces and newlines
$css = trim((string)preg_replace('/\s+/', ' ', (string)$css));

file_put_contents($dstCss, $css);
$minSize = strlen($css);
$savings = round((1 - ($minSize / $originalSize)) * 100, 1);

echo sprintf(
    "Asset build complete: %s\nOriginal: %d KB -> Minified: %d KB (Saved %s%%)\n",
    $dstCss,
    round($originalSize / 1024),
    round($minSize / 1024),
    $savings
);
