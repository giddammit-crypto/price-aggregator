<?php
declare(strict_types=1);

namespace App\Ingest;

use Generator;
use RuntimeException;

class YmlReader
{
    /**
     * Streams offers from a YML/XML file or stream without relying on xmlreader extension.
     * Uses pure PHP chunked streaming buffer: Memory usage < 2 MB regardless of file size.
     *
     * @param string $source Path to file or URL
     * @return Generator<array>
     */
    public function readOffers(string $source): Generator
    {
        $handle = @fopen($source, 'rb');
        if (!$handle) {
            throw new RuntimeException("Cannot open XML/YML source: {$source}");
        }

        $buffer = '';
        $chunkSize = 65536; // 64 KB

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $buffer .= $chunk;

                // Extract all complete <offer>...</offer> blocks from buffer
                while (($startPos = stripos($buffer, '<offer')) !== false) {
                    $closePos = stripos($buffer, '</offer>', $startPos);
                    if ($closePos === false) {
                        // Check if it's a self-closing <offer ... />
                        $nextTag = strpos($buffer, '>', $startPos);
                        if ($nextTag !== false && substr($buffer, $nextTag - 1, 1) === '/') {
                            $offerBlock = substr($buffer, $startPos, $nextTag - $startPos + 1);
                            $buffer = substr($buffer, $nextTag + 1);
                            $offer = $this->parseOfferBlock($offerBlock);
                            if ($offer) {
                                yield $offer;
                            }
                            continue;
                        }
                        // Incomplete block, read more into buffer
                        break;
                    }

                    $endPos = $closePos + strlen('</offer>');
                    $offerBlock = substr($buffer, $startPos, $endPos - $startPos);
                    $buffer = substr($buffer, $endPos);

                    $offer = $this->parseOfferBlock($offerBlock);
                    if ($offer) {
                        yield $offer;
                    }
                }

                // Keep memory low: prevent buffer from growing endlessly if no offers found
                if (strlen($buffer) > 524288) { // 512 KB
                    $lastTag = strrpos($buffer, '<');
                    if ($lastTag !== false) {
                        $buffer = substr($buffer, $lastTag);
                    } else {
                        $buffer = '';
                    }
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parses an individual XML offer string into structured array.
     */
    public function parseOfferBlock(string $xml): ?array
    {
        // Extract <offer ...> open tag attributes
        if (!preg_match('/<offer\b([^>]*)>/i', $xml, $m)) {
            return null;
        }

        $attrStr = $m[1];
        $id = '';
        if (preg_match('/id=["\']([^"\']+)["\']/i', $attrStr, $am)) {
            $id = $am[1];
        } else {
            $id = (string)uniqid();
        }

        $available = true;
        if (preg_match('/available=["\']([^"\']+)["\']/i', $attrStr, $am)) {
            $available = filter_var($am[1], FILTER_VALIDATE_BOOLEAN);
        }

        $model = $this->extractTag($xml, 'model') ?: '';
        $name = $this->extractTag($xml, 'name') ?: $model;
        if (!$name) {
            $prefix = $this->extractTag($xml, 'typePrefix');
            $vendor = $this->extractTag($xml, 'vendor');
            if ($model || $vendor) {
                $name = trim("{$prefix} {$vendor} {$model}");
            }
        }

        $price = (float)($this->extractTag($xml, 'price') ?: 0);
        $oldPriceStr = $this->extractTag($xml, 'oldprice');
        $oldPrice = $oldPriceStr !== null ? (float)$oldPriceStr : null;
        $currencyId = $this->extractTag($xml, 'currencyId') ?: 'RUB';
        $categoryId = $this->extractTag($xml, 'categoryId') ?: '';
        $url = $this->extractTag($xml, 'url') ?: '';
        $vendor = $this->extractTag($xml, 'vendor') ?: '';
        $vendorCode = $this->extractTag($xml, 'vendorCode') ?: '';
        $barcode = $this->extractTag($xml, 'barcode') ?: '';
        $description = $this->extractTag($xml, 'description') ?: '';

        // Extract pictures
        $pictures = [];
        if (preg_match_all('/<picture\b[^>]*>(.*?)<\/picture>/is', $xml, $picMatches)) {
            foreach ($picMatches[1] as $p) {
                $pClean = trim($p);
                if ($pClean !== '') {
                    $pictures[] = $pClean;
                }
            }
        }

        // Extract params
        $params = [];
        if (preg_match_all('/<param\s+name=["\']([^"\']+)["\'][^>]*>(.*?)<\/param>/is', $xml, $pMatches, PREG_SET_ORDER)) {
            foreach ($pMatches as $pm) {
                $pName = trim($pm[1]);
                $pVal = trim(strip_tags($pm[2]));
                if ($pName !== '') {
                    $params[$pName] = $pVal;
                }
            }
        }

        $deliveryStr = $this->extractTag($xml, 'delivery');
        $delivery = $deliveryStr !== null ? filter_var($deliveryStr, FILTER_VALIDATE_BOOLEAN) : true;

        return [
            'id' => $id,
            'available' => $available,
            'name' => html_entity_decode((string)$name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'model' => html_entity_decode((string)$model, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'price' => $price,
            'oldprice' => $oldPrice,
            'currencyId' => $currencyId,
            'categoryId' => $categoryId,
            'picture' => $pictures[0] ?? null,
            'pictures' => $pictures,
            'url' => html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'vendor' => html_entity_decode($vendor, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'vendorCode' => $vendorCode,
            'barcode' => $barcode,
            'description' => $description,
            'param' => $params,
            'delivery' => $delivery,
        ];
    }

    private function extractTag(string $xml, string $tag): ?string
    {
        if (preg_match('/<' . $tag . '\b[^>]*>(.*?)<\/' . $tag . '>/is', $xml, $m)) {
            $val = trim($m[1]);
            // Strip CDATA if present
            if (str_starts_with($val, '<![CDATA[') && str_ends_with($val, ']]>')) {
                $val = substr($val, 9, -3);
            }
            return trim($val);
        }
        return null;
    }
}
