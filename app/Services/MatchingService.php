<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Utf8;
use App\Storage\Fs;
use App\Storage\Pack;
use App\Storage\Repositories\FileOfferRepository;

class MatchingService
{
    private string $stateDir;
    private string $overridesFile;
    private string $queueFile;
    private array $overrides = [];

    public function __construct(string $stateDir = '')
    {
        $root = dirname(__DIR__, 2);
        $this->stateDir = rtrim($stateDir ?: ($root . '/data/state'), '/') . '/';
        $this->overridesFile = $this->stateDir . 'match_overrides.json';
        $this->queueFile = $this->stateDir . 'matching_queue.ndjson';

        if (file_exists($this->overridesFile)) {
            $this->overrides = @json_decode((string)file_get_contents($this->overridesFile), true) ?: [];
        }
    }

    /**
     * Attempts to match an offer against products catalog.
     *
     * @param array $offer Normalized offer
     * @param array<int, array> $candidateProducts Candidate products (e.g. from same category)
     * @return array{matched: bool, product_id: ?int, score: float, method: string}
     */
    public function matchOffer(array $offer, array $candidateProducts): array
    {
        $offerKey = (string)($offer['key'] ?? '');

        // 1. Manual override check
        if (isset($this->overrides[$offerKey])) {
            return [
                'matched' => true,
                'product_id' => (int)$this->overrides[$offerKey],
                'score' => 1.0,
                'method' => 'manual_override'
            ];
        }

        $offerEan = (string)($offer['ean'] ?? '');
        $offerMpn = strtoupper(trim((string)($offer['mpn'] ?? '')));
        $offerTitle = Utf8::strtolower((string)($offer['title'] ?? ''));

        $bestScore = 0.0;
        $bestProductId = null;
        $bestMethod = 'none';

        foreach ($candidateProducts as $prod) {
            $prodId = (int)$prod['id'];

            // 2. Barcode / EAN exact match
            if ($offerEan !== '' && !empty($prod['barcode']) && $prod['barcode'] === $offerEan) {
                return [
                    'matched' => true,
                    'product_id' => $prodId,
                    'score' => 1.0,
                    'method' => 'ean_exact'
                ];
            }

            // 3. MPN exact match
            $prodMpn = strtoupper(trim((string)($prod['mpn'] ?? '')));
            if ($offerMpn !== '' && $prodMpn !== '' && $offerMpn === $prodMpn) {
                return [
                    'matched' => true,
                    'product_id' => $prodId,
                    'score' => 1.0,
                    'method' => 'mpn_exact'
                ];
            }

            // 4. Token similarity matching
            $prodTitle = Utf8::strtolower((string)$prod['title']);
            $score = $this->calculateSimilarity($offerTitle, $prodTitle);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestProductId = $prodId;
                $bestMethod = 'token_similarity';
            }
        }

        // Auto-match threshold
        if ($bestScore >= 0.90 && $bestProductId !== null) {
            return [
                'matched' => true,
                'product_id' => $bestProductId,
                'score' => $bestScore,
                'method' => $bestMethod
            ];
        }

        // Potential match for review queue (0.70 - 0.89)
        if ($bestScore >= 0.70 && $bestProductId !== null) {
            $this->enqueueForReview($offer, $bestProductId, $bestScore);
        }

        return [
            'matched' => false,
            'product_id' => null,
            'score' => $bestScore,
            'method' => 'none'
        ];
    }

    private function enqueueForReview(array $offer, int $suggestedProductId, float $score): void
    {
        $entry = [
            'date' => date('Y-m-d H:i:s'),
            'offer_key' => $offer['key'] ?? '',
            'offer_title' => $offer['title'] ?? '',
            'offer_price' => $offer['price'] ?? 0,
            'shop' => $offer['shop'] ?? '',
            'suggested_product_id' => $suggestedProductId,
            'score' => round($score, 3)
        ];

        Fs::appendLine($this->queueFile, (string)json_encode($entry, JSON_UNESCAPED_UNICODE));
    }

    private function calculateSimilarity(string $s1, string $s2): float
    {
        $tokens1 = array_unique(array_filter(preg_split('/[^a-z0-9а-яё]+/u', $s1) ?: []));
        $tokens2 = array_unique(array_filter(preg_split('/[^a-z0-9а-яё]+/u', $s2) ?: []));

        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($tokens1, $tokens2));
        $total = count($tokens1) + count($tokens2);

        // Dice coefficient
        return (2.0 * $intersection) / (float)$total;
    }

    public function setOverride(string $offerKey, int $productId): bool
    {
        $this->overrides[$offerKey] = $productId;
        return Fs::atomicWrite(
            $this->overridesFile,
            (string)json_encode($this->overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function getReviewQueue(int $limit = 50): array
    {
        if (!file_exists($this->queueFile)) {
            return [];
        }

        $lines = file($this->queueFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        $items = [];
        $count = 0;
        foreach (array_reverse($lines) as $l) {
            $item = @json_decode($l, true);
            if ($item) {
                $items[] = $item;
                $count++;
                if ($count >= $limit) {
                    break;
                }
            }
        }

        return $items;
    }
}
