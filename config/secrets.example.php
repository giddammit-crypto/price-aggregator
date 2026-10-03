<?php
declare(strict_types=1);

return [
    "admin_password_hash" => "$2y$12$SyJiaHrh8P9uybVpZ3Z5E.T1Ly.VNO3dNf1WsMBD586HZF9lvuqZy", // Default: PriceHubAdmin2026!
    "admin_totp_secret" => "",
    "aliexpress" => [
        "app_key" => "YOUR_ALIEXPRESS_APP_KEY",
        "app_secret" => "YOUR_ALIEXPRESS_APP_SECRET",
        "tracking_id" => "YOUR_TRACKING_ID"
    ],
    "telegram_bot" => [
        "token" => "",
        "chat_id" => ""
    ],
    "smtp" => [
        "host" => "localhost",
        "port" => 25,
        "user" => "",
        "pass" => "",
        "secure" => ""
    ]
];
