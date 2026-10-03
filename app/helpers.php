<?php
/**
 * Global application helpers
 */

declare(strict_types=1);

use App\Core\Utf8;
use App\Core\Csrf;

if (!function_exists('e')) {
    function e(?string $text): string
    {
        if ($text === null) {
            return '';
        }
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice(float|int|null $price): string
    {
        if ($price === null) {
            return '—';
        }
        return number_format((float)$price, 0, '.', "\u{00A0}") . "\u{00A0}₽";
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = e(csrf_token());
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        return Utf8::slugify($text);
    }
}

if (!function_exists('mb_strlen') && !function_exists('mb_strlen_compat')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return Utf8::strlen($string);
    }
}

if (!function_exists('mb_substr') && !function_exists('mb_substr_compat')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return Utf8::substr($string, $start, $length);
    }
}

if (!function_exists('mb_strtolower') && !function_exists('mb_strtolower_compat')) {
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        return Utf8::strtolower($string);
    }
}

if (!function_exists('mb_strtoupper') && !function_exists('mb_strtoupper_compat')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string
    {
        return Utf8::strtoupper($string);
    }
}
