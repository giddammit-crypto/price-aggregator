<?php
declare(strict_types=1);

namespace App\Ingest;

use App\Core\Logger;
use App\Storage\Fs;

class FxRatesService
{
    private string $stateFile;
    private HttpClient $http;
    private array $rates = [];

    private const FALLBACK_RATES = [
        'RUB' => 1.0,
        'USD' => 92.50,
        'EUR' => 101.20,
        'CNY' => 12.85,
        'BYN' => 28.50,
        'KZT' => 0.19,
    ];

    public function __construct(string $stateFile = '', ?HttpClient $http = null)
    {
        $root = dirname(__DIR__, 2);
        $this->stateFile = $stateFile ?: ($root . '/data/state/fx_rates.json');
        $this->http = $http ?: new HttpClient();
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->stateFile)) {
            $data = @json_decode((string)file_get_contents($this->stateFile), true);
            if (is_array($data) && !empty($data['rates'])) {
                $this->rates = $data['rates'];
                return;
            }
        }
        $this->rates = self::FALLBACK_RATES;
    }

    public function updateRates(): bool
    {
        $url = 'https://www.cbr.ru/scripts/XML_daily.asp';
        $res = $this->http->get($url, [], 2, false);

        if ($res['status'] !== 200 || empty($res['body'])) {
            Logger::warning("FxRatesService: failed to fetch CBR rates, using fallback.");
            return false;
        }

        try {
            $xml = @simplexml_load_string($res['body']);
            if (!$xml || !isset($xml->Valute)) {
                return false;
            }

            $rates = ['RUB' => 1.0];
            foreach ($xml->Valute as $v) {
                $char = (string)$v->CharCode;
                $nominal = (int)$v->Nominal ?: 1;
                $valStr = str_replace(',', '.', (string)$v->Value);
                $val = (float)$valStr;

                if ($val > 0) {
                    $rates[$char] = round($val / $nominal, 4);
                }
            }

            // Merge with fallback to ensure CNY, USD, EUR are always set
            foreach (self::FALLBACK_RATES as $code => $default) {
                if (!isset($rates[$code])) {
                    $rates[$code] = $default;
                }
            }

            $payload = [
                'updated_at' => date('Y-m-d H:i:s'),
                'source' => 'CBR',
                'rates' => $rates,
            ];

            Fs::atomicWrite($this->stateFile, (string)json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->rates = $rates;
            Logger::info("FxRatesService: updated official exchange rates.");
            return true;
        } catch (\Throwable $e) {
            Logger::error("FxRatesService parse error: " . $e->getMessage());
            return false;
        }
    }

    public function getRates(): array
    {
        return $this->rates;
    }

    public function convert(float $amount, string $from, string $to = 'RUB'): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return $amount;
        }

        $fromRate = $this->rates[$from] ?? self::FALLBACK_RATES[$from] ?? 1.0;
        $toRate = $this->rates[$to] ?? self::FALLBACK_RATES[$to] ?? 1.0;

        if ($toRate <= 0) {
            return $amount;
        }

        // Convert from source to RUB, then from RUB to target
        $rub = $amount * $fromRate;
        return round($rub / $toRate, 2);
    }
}
