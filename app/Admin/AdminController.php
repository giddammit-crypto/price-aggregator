<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\BuildIndexService;
use App\Services\MatchingService;
use App\Storage\Snapshot;

class AdminController
{
    private function isAuthenticated(Request $request): bool
    {
        Csrf::getToken(); // Start the session before checking the persisted login.
        return !empty($_SESSION['admin_auth']);
    }

    public function loginForm(Request $request): Response
    {
        return $this->login($request);
    }

    public function loginSubmit(Request $request): Response
    {
        return $this->login($request);
    }

    public function matchingQueue(Request $request): Response
    {
        return $this->matching($request);
    }

    public function matchingResolve(Request $request): Response
    {
        return $this->saveOverride($request);
    }

    public function reports(Request $request): Response
    {
        return $this->quarantine($request);
    }

    public function health(Request $request): Response
    {
        $root = dirname(__DIR__, 2);
        return Response::json([
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'snapshot' => Snapshot::getActiveId(),
            'php_version' => PHP_VERSION,
            'opcache_enabled' => function_exists('opcache_get_status') && !empty(opcache_get_status()['opcache_enabled']),
            'disk_free_mb' => round(disk_free_space($root) / 1048576, 2),
        ]);
    }

    public function login(Request $request): Response
    {
        if ($this->isAuthenticated($request)) {
            return Response::redirect('/admin');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $password = (string)$request->getPost('password', '');
            $secrets = Config::get('secrets', []);
            $hash = (string)($secrets['admin_password_hash'] ?? '');

            if ($hash && password_verify($password, $hash)) {
                session_regenerate_id(true);
                $_SESSION['admin_auth'] = [
                    'logged_in_at' => time(),
                    'user' => 'admin'
                ];
                return Response::redirect('/admin');
            } else {
                $error = 'Неверный пароль администратора.';
            }
        }

        return Response::html(View::make('admin/login', [
            'error' => $error,
            'title' => 'Вход в панель управления'
        ]));
    }

    public function logout(Request $request): Response
    {
        unset($_SESSION['admin_auth']);
        return Response::redirect('/admin/login');
    }

    public function index(Request $request): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::redirect('/admin/login');
        }

        $root = dirname(__DIR__, 2);
        $snapshotId = Snapshot::getActiveId() ?: 'none';
        $meta = Snapshot::loadArray('meta.php', []);

        // Read cron job cursors
        $cursorsDir = $root . '/data/state/cursors';
        $cronJobs = [];
        if (is_dir($cursorsDir)) {
            foreach (glob($cursorsDir . '/*.json') ?: [] as $cFile) {
                $data = @json_decode((string)file_get_contents($cFile), true);
                if ($data) {
                    $cronJobs[] = $data;
                }
            }
        }

        // Today's clicks count
        $clicksToday = 0;
        $clickFile = $root . '/data/logs/clicks/' . date('Y-m-d') . '.ndjson';
        if (file_exists($clickFile)) {
            $clicksToday = count(file($clickFile, FILE_SKIP_EMPTY_LINES) ?: []);
        }

        // FX rates
        $fxFile = $root . '/data/state/fx_rates.json';
        $fxData = file_exists($fxFile) ? @json_decode((string)file_get_contents($fxFile), true) : [];

        // Inodes estimate
        $packDir = $root . '/data/catalog/products';
        $productShardsCount = count(glob($packDir . '/p*.ndjson') ?: []);

        return Response::html(View::make('admin/index', [
            'title' => 'Панель управления — Обзор',
            'snapshotId' => $snapshotId,
            'meta' => $meta,
            'cronJobs' => $cronJobs,
            'clicksToday' => $clicksToday,
            'fxData' => $fxData,
            'productShardsCount' => $productShardsCount,
        ]));
    }

    public function shops(Request $request): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::redirect('/admin/login');
        }

        $shops = Config::get('shops', []);
        $root = dirname(__DIR__, 2);

        $shopStats = [];
        foreach ($shops as $id => $s) {
            $cursorFile = $root . "/data/state/cursors/ingest_{$id}.json";
            $lastRun = 'Never';
            $metrics = null;
            if (file_exists($cursorFile)) {
                $c = @json_decode((string)file_get_contents($cursorFile), true);
                $lastRun = $c['last_run'] ?? 'Never';
                $metrics = $c['metrics'] ?? null;
            }

            $shopStats[] = [
                'id' => $id,
                'name' => $s['name'],
                'kind' => $s['kind'],
                'mode' => $s['mode'],
                'trust_level' => $s['trust_level'],
                'badge' => $s['badge'] ?? '',
                'last_run' => $lastRun,
                'metrics' => $metrics,
            ];
        }

        return Response::html(View::make('admin/shops', [
            'title' => 'Магазины и маркетплейсы',
            'shops' => $shopStats,
        ]));
    }

    public function matching(Request $request): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::redirect('/admin/login');
        }

        $matchingService = new MatchingService();
        $queue = $matchingService->getReviewQueue(100);

        return Response::html(View::make('admin/matching', [
            'title' => 'Очередь сопоставления (Матчинг)',
            'queue' => $queue,
        ]));
    }

    public function saveOverride(Request $request): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        if (!Csrf::validate((string)$request->getPost('_csrf', ''))) {
            return Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $offerKey = (string)$request->getPost('offer_key', '');
        $productId = (int)$request->getPost('product_id', 0);

        if (!$offerKey || $productId <= 0) {
            return Response::json(['ok' => false, 'error' => 'Invalid parameters'], 400);
        }

        $matchingService = new MatchingService();
        $ok = $matchingService->setOverride($offerKey, $productId);

        return Response::json(['ok' => $ok]);
    }

    public function quarantine(Request $request): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::redirect('/admin/login');
        }

        $root = dirname(__DIR__, 2);
        $qFile = $root . '/data/state/quarantine.ndjson';
        $items = [];

        if (file_exists($qFile)) {
            $lines = file($qFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach (array_slice(array_reverse($lines), 0, 100) as $l) {
                $dec = @json_decode($l, true);
                if ($dec) {
                    $items[] = $dec;
                }
            }
        }

        return Response::html(View::make('admin/quarantine', [
            'title' => 'Карантин цен и аномалий',
            'items' => $items,
        ]));
    }

    public function rebuildIndex(Request $request): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $root = dirname(__DIR__, 2);
        $service = new BuildIndexService($root);
        $msg = $service->run();

        return Response::json(['ok' => true, 'message' => $msg]);
    }
}
