<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Storage\Snapshot;

class HomeController
{
    public function index(Request $request): Response
    {
        $homeData = Snapshot::loadArray('home.php', [
            'drops' => [],
            'popular' => [],
            'newest' => []
        ]);
        $categories = Snapshot::loadArray('categories.php', []);
        $brands = Snapshot::loadArray('brands.php', []);
        $meta = Snapshot::loadArray('meta.php', []);

        $view = new View([
            'drops' => $homeData['drops'] ?? [],
            'popular' => $homeData['popular'] ?? [],
            'newest' => $homeData['newest'] ?? [],
            'categories' => $categories,
            'topBrands' => array_slice(array_keys($brands), 0, 14),
            'meta' => $meta,
            'pageTitle' => 'PriceHub — Сравнение цен в интернет-магазинах и маркетплейсах РФ и Китая',
            'pageDesc' => 'Агрегатор цен на электронику, смартфоны, ПК-комплектующие и бытовую технику. Сравните цены магазинов, маркетплейсов и продавцов из Китая в одном месте.'
        ]);

        $html = $view->render('pages/home');
        return Response::html($html);
    }
}
