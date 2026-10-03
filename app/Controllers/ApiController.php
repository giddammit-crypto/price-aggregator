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
        $repo = new FileHistoryRepository();
        $history = $repo->getHistory($id, 90);
        return Response::json($history);
    }
}
