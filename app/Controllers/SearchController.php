<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\SearchService;
use App\Storage\Fs;

class SearchController
{
    private SearchService $searchService;

    public function __construct()
    {
        $this->searchService = new SearchService();
    }

    public function index(Request $request): Response
    {
        $query = trim((string)$request->getQuery('q', ''));
        $startTime = microtime(true);

        $results = [];
        if ($query !== '') {
            $results = $this->searchService->search($query, 48);

            // Log queries without results for vocabulary and synonym replenishment
            if (empty($results)) {
                $logFile = dirname(__DIR__, 2) . '/data/logs/noresult/' . date('Y-m-d') . '.ndjson';
                Fs::appendLine($logFile, json_encode([
                    'ts' => time(),
                    'query' => $query,
                    'ip_hash' => hash('xxh128', $request->getClientIp())
                ], JSON_UNESCAPED_UNICODE));
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 1);

        $view = new View([
            'query' => $query,
            'results' => $results,
            'durationMs' => $duration,
            'pageTitle' => "Поиск: «{$query}» — PriceHub",
            'pageDesc' => "Результаты поиска по запросу «{$query}». Сравнение цен в проверенных магазинах и маркетплейсах."
        ]);

        return Response::html($view->render('pages/search'));
    }
}
