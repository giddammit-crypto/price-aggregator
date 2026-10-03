<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Storage\Pack;

class CompareController
{
    public function index(Request $request): Response
    {
        $idsParam = (string)$request->getQuery('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));

        $products = [];
        if (!empty($ids)) {
            $products = array_filter(Pack::getMultiple(array_slice($ids, 0, 6)), static fn(array $p): bool => !empty($p['pub'])); // cap compare at 6 items
        }

        // Collect all distinct specification keys
        $allSpecKeys = [];
        foreach ($products as $p) {
            foreach ($p['specs'] ?? [] as $k => $v) {
                $allSpecKeys[$k] = true;
            }
        }
        $specKeys = array_keys($allSpecKeys);

        $view = new View([
            'products' => array_values($products),
            'specKeys' => $specKeys,
            'pageTitle' => 'Сравнение товаров — PriceHub',
            'pageDesc' => 'Сравнение характеристик и лучших цен на электронику. Наглядная таблица отличий моделей.'
        ]);

        return Response::html($view->render('pages/compare'));
    }

    public function favorites(Request $request): Response
    {
        $idsParam = (string)$request->getQuery('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));

        $products = [];
        if (!empty($ids)) {
            $products = array_filter(Pack::getMultiple(array_slice($ids, 0, 40)), static fn(array $p): bool => !empty($p['pub']));
        }

        $view = new View([
            'products' => array_values($products),
            'pageTitle' => 'Избранные товары — PriceHub',
            'pageDesc' => 'Ваш список отслеживаемых товаров и цен.'
        ]);

        return Response::html($view->render('pages/favorites'));
    }
}
