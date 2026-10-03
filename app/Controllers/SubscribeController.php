<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Csrf;

class SubscribeController
{
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
        if ($productId <= 0 || !is_finite($targetPrice) || $targetPrice <= 0) {
            return Response::json(['error' => 'Укажите товар и целевую цену выше нуля.'], 400);
        }

        // No outbound mail transport or confirmation route is configured. An
        // unconfirmable record would never be notified: do not store personal
        // data or claim that an email has been sent.
        return Response::json(['error' => 'Уведомления временно недоступны: отправка подтверждений не настроена.'], 503);
    }
}
