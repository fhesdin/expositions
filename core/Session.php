<?php

namespace Core;

/**
 * Gestion de session avec timeout d'inactivité.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $timeout = Env::getInt('SESSION_TIMEOUT_MINUTES', 30) * 60;

        session_name('expo_sess');
        session_set_cookie_params([
            'lifetime' => $timeout,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => Env::getBool('APP_DEBUG') ? false : true,
        ]);
        session_start();
        self::$started = true;

        // Timeout d'inactivité
        $now = time();
        $last = $_SESSION['_last_activity'] ?? $now;
        if ($now - $last > $timeout) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = $now;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Stocke un message flash. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Consomme et retourne tous les messages flash. */
    public static function flushFlashes(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
