<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Storage\Snapshot;

class CatalogController
{
    private const PER_PAGE = 24;

    public function category(Request $request, array $params): Response
    {
        $slug = $params['cat'] ?? '';
        $categories = Snapshot::loadArray('categories.php', []);

        // Find category by slug
        $currentCat = null;
        $catId = null;
        foreach ($categories as $id => $c) {
            if ($c['slug'] === $slug) {
                $currentCat = $c;
                $catId = (int)$id;
                break;
            }
        }

        if (!$currentCat || $catId === null) {
            // Parent category or 404
            return Response::html('<h1>Категория не найдена</h1>', 404);
        }

        // Load pre-compiled rows and facets
        $allRows = Snapshot::loadArray("cat/{$catId}.php", []);
        $facets = Snapshot::loadArray("cat/{$catId}.facets.php", []);

        // Filter and sort
        $filtered = $this->filterRows($allRows, $request);
        $sorted = $this->sortRows($filtered, $request->getQuery('sort', 'popular'));

        $total = count($sorted);
        $page = max(1, (int)$request->getQuery('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $items = array_slice($sorted, $offset, self::PER_PAGE);
        $totalPages = (int)ceil($total / self::PER_PAGE);

        $view = new View([
            'category' => $currentCat,
            'catId' => $catId,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'facets' => $facets,
            'selectedBrand' => $request->getQuery('brand', ''),
            'selectedPriceFrom' => $request->getQuery('price_from', ''),
            'selectedPriceTo' => $request->getQuery('price_to', ''),
            'selectedSeller' => $request->getQuery('seller', ''),
            'currentSort' => $request->getQuery('sort', 'popular'),
            'pageTitle' => "Купить {$currentCat['name']} — сравнение цен в магазинах и маркетплейсах",
            'pageDesc' => "Все актуальные цены на {$currentCat['name']} в магазинах РФ и маркетплейсах. Сравнение предложений, история цен, фильтры по характеристикам."
        ]);

        return Response::html($view->render('pages/catalog'));
    }

    public function filterPartial(Request $request): Response
    {
        $catId = (int)$request->getQuery('catId', 14);
        $allRows = Snapshot::loadArray("cat/{$catId}.php", []);

        $filtered = $this->filterRows($allRows, $request);
        $sorted = $this->sortRows($filtered, $request->getQuery('sort', 'popular'));

        $total = count($sorted);
        $page = max(1, (int)$request->getQuery('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $items = array_slice($sorted, $offset, self::PER_PAGE);

        $view = new View();
        $html = '';
        foreach ($items as $p) {
            $html .= $view->partial('partials/product_card', ['p' => $p]);
        }
        if (empty($html)) {
            $html = '<div style="grid-column: 1/-1; padding: 40px; text-align: center; background: #FFF; border-radius: 8px;">Товаров по выбранным фильтрам не найдено. Попробуйте сбросить параметры.</div>';
        }

        return Response::json([
            'html' => $html,
            'count' => $total
        ]);
    }

    private function filterRows(array $rows, Request $request): array
    {
        $brand = (string)$request->getQuery('brand', '');
        $priceFrom = (float)$request->getQuery('price_from', 0);
        $priceTo = (float)$request->getQuery('price_to', 0);
        $seller = (string)$request->getQuery('seller', '');
        $onlyDrop = (bool)$request->getQuery('drop', false);

        return array_filter($rows, function ($r) use ($brand, $priceFrom, $priceTo, $seller, $onlyDrop) {
            if ($brand !== '' && $r['b'] !== $brand) {
                return false;
            }
            if ($priceFrom > 0 && $r['p'] < $priceFrom) {
                return false;
            }
            if ($priceTo > 0 && $r['p'] > $priceTo) {
                return false;
            }
            if ($seller === 'marketplace' && empty($r['mp'])) {
                return false;
            }
            if ($seller === 'crossborder' && empty($r['cb'])) {
                return false;
            }
            if ($onlyDrop && ($r['d'] ?? 0) < 8.0) {
                return false;
            }
            return true;
        });
    }

    private function sortRows(array $rows, string $sort): array
    {
        switch ($sort) {
            case 'price_asc':
                usort($rows, fn($a, $b) => $a['p'] <=> $b['p']);
                break;
            case 'price_desc':
                usort($rows, fn($a, $b) => $b['p'] <=> $a['p']);
                break;
            case 'drop':
                usort($rows, fn($a, $b) => ($b['d'] ?? 0) <=> ($a['d'] ?? 0));
                break;
            case 'new':
                usort($rows, fn($a, $b) => $b['id'] <=> $a['id']);
                break;
            case 'popular':
            default:
                usort($rows, fn($a, $b) => $b['pop'] <=> $a['pop']);
                break;
        }
        return $rows;
    }
}
