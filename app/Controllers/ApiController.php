<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\SearchService;
use App\Storage\Repositories\FileHistoryRepository;

class ApiController
{
    private SearchService $searchService;

    public function __construct()
    {
        $this->searchService = new SearchService();
    }

    public function suggest(Request $request): Response
    {
        $q = (string)$request->getQuery('q', '');
        $data = $this->searchService->suggest($q, 6);
        return Response::json($data);
    }

    public function history(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return Response::json(['error' => 'Invalid product ID'], 400);
        }
        $repo = new FileHistoryRepository();
        $history = $repo->getHistory($id, 90);
        return Response::json($history);
    }

    public function searchIndex(Request $request): Response
    {
        $rootPath = dirname(__DIR__, 2);
        $file = $rootPath . '/public/api/search_index.json';
        if (!file_exists($file)) {
            \App\Services\SearchIndexGenerator::write($rootPath . '/public/api');
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return Response::json(['error' => 'Index unavailable'], 500);
        }

        return new Response($content, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }

    public function suggestIndex(Request $request): Response
    {
        $rootPath = dirname(__DIR__, 2);
        $file = $rootPath . '/public/api/suggest.json';
        if (!file_exists($file)) {
            \App\Services\SearchIndexGenerator::write($rootPath . '/public/api');
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return Response::json(['error' => 'Suggest unavailable'], 500);
        }

        return new Response($content, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
}
