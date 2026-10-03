<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Csrf;
use App\Storage\Repositories\FileSubsRepository;

class SubscribeController
{
    private FileSubsRepository $subsRepo;

    public function __construct()
    {
        $this->subsRepo = new FileSubsRepository();
    }

    public function subscribe(Request $request): Response
    {
        $token = (string)$request->getPost('_csrf', '');
        if (!Csrf::validate($token)) {
            return Response::json(['error' => 'Недействительный CSRF-токен.'], 403);
        }

        $email = trim((string)$request->getPost('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['error' => 'Пожалуйста, введите корректный e-mail.'], 400);
        }

        $productId = (int)$request->getPost('product_id', 0);
        $targetPrice = (float)$request->getPost('target_price', 0);

        $record = $this->subsRepo->subscribe($email, $productId, $targetPrice);

        return Response::json([
            'success' => true,
            'message' => 'Спасибо! На ваш e-mail отправлена ссылка для подтверждения подписки на снижение цены.'
        ]);
    }
}
