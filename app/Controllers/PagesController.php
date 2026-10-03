<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Csrf;
use App\Storage\Fs;
use App\Storage\Snapshot;

class PagesController
{
    public function howItWorks(Request $request): Response
    {
        $view = new View([
            'pageTitle' => 'Как мы считаем цены — PriceHub',
            'pageDesc' => 'Методология сбора цен, регулярность обновлений, расчет итоговой стоимости с доставкой и курсы валют.'
        ]);
        return Response::html($view->render('pages/how_it_works'));
    }

    public function marketplaces(Request $request): Response
    {
        $view = new View([
            'pageTitle' => 'О продавцах и маркетплейсах — PriceHub',
            'pageDesc' => 'Как мы проверяем магазины и защищаем пользователей от подделок, серых продавцов и муляжей.'
        ]);
        return Response::html($view->render('pages/marketplaces'));
    }

    public function reportForm(Request $request): Response
    {
        $view = new View([
            'pageTitle' => 'Сообщить о неверной цене или ошибке — PriceHub',
            'pageDesc' => 'Форма обратной связи для оперативной модерации предложений магазинов.'
        ]);
        return Response::html($view->render('pages/report'));
    }

    public function reportSubmit(Request $request): Response
    {
        $token = (string)$request->getPost('_csrf', '');
        if (!Csrf::validate($token)) {
            return Response::html('<h1>CSRF Token Invalid</h1>', 403);
        }

        $offerKey = trim((string)$request->getPost('offer_key', ''));
        $productId = (int)$request->getPost('product_id', 0);
        $reason = trim((string)$request->getPost('reason', ''));
        $comment = trim((string)$request->getPost('comment', ''));

        $logFile = dirname(__DIR__, 2) . '/data/logs/reports/' . date('Y-m-d') . '.ndjson';
        Fs::appendLine($logFile, json_encode([
            'ts' => time(),
            'offer_key' => $offerKey,
            'product_id' => $productId,
            'reason' => $reason,
            'comment' => $comment,
            'ip' => $request->getClientIp()
        ], JSON_UNESCAPED_UNICODE));

        $view = new View([
            'success' => true,
            'pageTitle' => 'Жалоба принята — PriceHub'
        ]);
        return Response::html($view->render('pages/report_success'));
    }

    public function legal(Request $request): Response
    {
        $view = new View([
            'pageTitle' => 'Правовая информация и конфиденциальность (152-ФЗ) — PriceHub',
            'pageDesc' => 'Политика обработки персональных данных, использование cookies и правовой статус агрегатора.'
        ]);
        return Response::html($view->render('pages/legal'));
    }

    public function uiKit(Request $request): Response
    {
        $view = new View([
            'pageTitle' => 'UI Kit — Дизайн-система PriceHub'
        ]);
        return Response::html($view->render('pages/ui_kit'));
    }

    public function sitemap(Request $request): Response
    {
        $urls = Snapshot::loadArray('sitemap/part_1.php', []);
        $baseUrl = \App\Core\Config::get('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Static
        $xml .= "  <url><loc>{$baseUrl}/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
        $categories = Snapshot::loadArray('categories.php', []);
        foreach ($categories as $cat) {
            $xml .= "  <url><loc>{$baseUrl}/catalog/{$cat['slug']}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>\n";
        }

        foreach (array_slice($urls, 0, 5000) as $path) {
            $xml .= "  <url><loc>{$baseUrl}{$path}</loc><changefreq>weekly</changefreq><priority>0.6</priority></url>\n";
        }
        $xml .= '</urlset>';

        return Response::html($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(Request $request): Response
    {
        $baseUrl = \App\Core\Config::get('app.url');
        $txt = "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /go/\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /api/\n";
        $txt .= "Sitemap: {$baseUrl}/sitemap.xml\n";

        return Response::html($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
