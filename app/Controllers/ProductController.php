<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Storage\Pack;
use App\Storage\Snapshot;
use App\Storage\Repositories\FileHistoryRepository;
use App\Services\PriceService;

class ProductController
{
    public function show(Request $request, array $params): Response
    {
        $rawId = (string)($params['id'] ?? '');
        if (!preg_match('/^[1-9][0-9]*$/D', $rawId) || (string)(int)$rawId !== $rawId) {
            return Response::html('<h1>Товар не найден</h1>', 404);
        }
        $id = (int)$rawId;
        $product = Pack::get($id);

        if (!$product || empty($product['pub'])) {
            return Response::html('<h1>Товар не найден</h1>', 404);
        }
        if (isset($params['slug']) && $params['slug'] !== $product['slug']) {
            return Response::redirect('/p/' . $product['slug'] . '-' . $id, 301);
        }

        $categories = Snapshot::loadArray('categories.php', []);
        $cat = $categories[$product['cat']] ?? ['name' => 'Каталог', 'slug' => 'pc-components'];

        // Offers grouping
        $offers = $product['offers'] ?? [];
        $bestOffer = PriceService::findBestOffer($offers);
        $groupedOffers = PriceService::groupOffersByKind($offers);

        // Price History from FileHistoryRepository
        $historyRepo = new FileHistoryRepository(dirname(__DIR__, 2) . '/data/history');
        $historyPoints = $historyRepo->getHistory($id, 90);

        // Similar products from same category and Next/Previous navigation
        $catRows = Snapshot::loadArray("cat/{$product['cat']}.php", []);
        $similar = [];
        $prevProduct = null;
        $nextProduct = null;
        $currIndex = -1;

        foreach ($catRows as $idx => $row) {
            if ($row['id'] === $id) {
                $currIndex = $idx;
                break;
            }
        }

        if ($currIndex > 0) {
            $prevProduct = $catRows[$currIndex - 1] ?? null;
        }
        if ($currIndex >= 0 && isset($catRows[$currIndex + 1])) {
            $nextProduct = $catRows[$currIndex + 1];
        }

        foreach ($catRows as $row) {
            if ($row['id'] !== $id) {
                $similar[] = $row;
                if (count($similar) >= 4) break;
            }
        }

        $view = new View([
            'product' => $product,
            'category' => $cat,
            'offers' => $offers,
            'bestOffer' => $bestOffer,
            'groupedOffers' => $groupedOffers,
            'historyPoints' => $historyPoints,
            'similar' => $similar,
            'prevProduct' => $prevProduct,
            'nextProduct' => $nextProduct,
            'pageTitle' => "{$product['title']} — сравнить цены от " . formatPrice($product['agg']['min'] ?? null),
            'pageDesc' => "Купить {$product['title']} по лучшей цене в магазинах и маркетплейсах. Характеристики, история изменения цен, отзывы и динамика скидок."
        ]);

        return Response::html($view->render('pages/product'));
    }
}
