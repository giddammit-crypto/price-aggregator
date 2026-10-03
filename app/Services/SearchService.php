<?php
/**
 * Search Engine Service
 * Inverted index lookup with Russian stemmer, layout correction, transliteration and synonyms.
 */

declare(strict_types=1);

namespace App\Services;

use App\Storage\Snapshot;
use App\Storage\Pack;
use App\Core\Utf8;

class SearchService
{
    private array $synonyms;

    public function __construct(string $root = '')
    {
        $root = $root ?: dirname(__DIR__, 2);
        $this->synonyms = require $root . '/config/synonyms.php';
    }

    /**
     * Search products by query string
     */
    public function search(string $query, int $limit = 40): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        // 1. Normalize and extract query tokens
        $tokens = $this->extractQueryTokens($query);
        if (empty($tokens)) {
            return [];
        }

        // 2. Fetch postings for each token
        $docScores = [];
        $matchedTokensCount = [];

        foreach ($tokens as $token) {
            $postings = $this->getPostingsForToken($token);
            foreach ($postings as $posting) {
                [$docId, $weight, $pop] = $posting;
                $score = ($weight * 10) + ($pop / 100);
                $docScores[$docId] = ($docScores[$docId] ?? 0) + $score;
                $matchedTokensCount[$docId] = ($matchedTokensCount[$docId] ?? 0) + 1;
            }
        }

        if (empty($docScores)) {
            // Try layout correction (e.g. "ghjwtccjh" -> "процессор")
            $fixedQuery = Utf8::fixKeyboardLayout($query);
            if ($fixedQuery !== Utf8::strtolower($query)) {
                return $this->search($fixedQuery, $limit);
            }
            return [];
        }

        // Boost items that match multiple query tokens
        foreach ($docScores as $docId => $score) {
            $matches = $matchedTokensCount[$docId] ?? 1;
            $docScores[$docId] = $score * ($matches * $matches);
        }

        arsort($docScores);
        $topDocIds = array_slice(array_keys($docScores), 0, $limit);

        // Fetch product data from Pack
        return Pack::getMultiple($topDocIds);
    }

    /**
     * Autocomplete suggestions
     */
    public function suggest(string $prefix, int $limit = 8): array
    {
        $prefix = Utf8::strtolower(trim($prefix));
        if (strlen($prefix) < 2) {
            return ['suggestions' => [], 'products' => []];
        }

        $vocab = Snapshot::loadArray('search/vocab.php', []);
        $matchingTerms = [];

        foreach ($vocab as $term) {
            if (str_starts_with((string)$term, $prefix)) {
                $matchingTerms[] = $term;
                if (count($matchingTerms) >= $limit) {
                    break;
                }
            }
        }

        // Quick top products for query
        $products = $this->search($prefix, 4);
        $compactProducts = [];
        foreach ($products as $p) {
            $compactProducts[] = [
                'id' => $p['id'],
                'title' => $p['title'],
                'slug' => $p['slug'],
                'price' => number_format((float)($p['agg']['min'] ?? 0), 0, '.', ' ')
            ];
        }

        return [
            'suggestions' => $matchingTerms,
            'products' => $compactProducts
        ];
    }

    private function extractQueryTokens(string $query): array
    {
        $lower = Utf8::strtolower(str_replace('ё', 'е', $query));
        preg_match_all('/[a-zа-я0-9]+/u', $lower, $m);
        $raw = $m[0] ?? [];

        $tokens = [];
        foreach ($raw as $tok) {
            if (strlen($tok) < 2) continue;
            $tokens[] = $tok;

            // Add synonym if exists
            if (isset($this->synonyms[$tok])) {
                $tokens[] = $this->synonyms[$tok];
            }

            // Split alphanumeric (e.g. rtx4070 -> rtx, 4070)
            if (preg_match('/^([a-zа-я]+)([0-9]+)$/u', $tok, $splitM)) {
                $tokens[] = $splitM[1];
                $tokens[] = $splitM[2];
            }

            // Stem Russian words (simple Russian suffix stripping)
            $stemmed = $this->stemRussian($tok);
            if ($stemmed !== $tok && strlen($stemmed) >= 3) {
                $tokens[] = $stemmed;
            }
        }

        return array_unique($tokens);
    }

    private function getPostingsForToken(string $token): array
    {
        $prefix = substr(hash('crc32b', $token), 0, 2);
        $file = "search/tok_{$prefix}.php";
        $data = Snapshot::loadArray($file, []);
        return $data[$token] ?? [];
    }

    /**
     * Lightweight Russian Stemmer (Snowball subset)
     */
    private function stemRussian(string $word): string
    {
        $suffixes = [
            'иями', 'ями', 'ей', 'ия', 'ья', 'ев', 'ов', 'ом', 'ам', 'ах', 'ях',
            'ого', 'его', 'ому', 'ему', 'ыми', 'ими', 'ый', 'ий', 'ой', 'ая', 'яя',
            'ое', 'ее', 'ые', 'ие', 'ых', 'их', 'ую', 'юю', 'ою', 'ею'
        ];

        foreach ($suffixes as $suf) {
            if (str_ends_with($word, $suf)) {
                $trimmed = substr($word, 0, -strlen($suf));
                if (strlen($trimmed) >= 3) {
                    return $trimmed;
                }
            }
        }
        return $word;
    }
}
