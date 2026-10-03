<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        self::ensureSession();
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        self::ensureSession();
        if ($token === null || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function regenerate(): string
    {
        self::ensureSession();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            if (session_module_name() === 'files') {
                $path = session_save_path();
                if ($path === '' || !is_dir($path) || !is_writable($path)) {
                    // Some shared hosts (and the local PHP CLI) have no usable
                    // default session directory. Keep sessions outside public/.
                    $path = dirname(__DIR__, 2) . '/data/state/sessions';
                    if (!is_dir($path) && !@mkdir($path, 0700, true) && !is_dir($path)) {
                        throw new \RuntimeException('Session storage is not writable');
                    }
                    session_save_path($path);
                }
            }
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', '1');
            if (!session_start()) {
                throw new \RuntimeException('Could not start a session');
            }
        }
    }
}
