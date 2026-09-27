<?php

namespace Core;

/**
 * Gestion du jeton CSRF.
 */
final class Csrf
{
    public static function token(): string
    {
        Session::start();
        $token = Session::get('_csrf');
        if ($token === null) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function check(): bool
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        $session = Session::get('_csrf');
        return $token !== null && $session !== null && hash_equals($session, $token);
    }

    /** Champ hidden à insérer dans les formulaires. */
    public static function field(): string
    {
        $t = self::token();
        return '<input type="hidden" name="_csrf" value="' . e($t) . '">';
    }

    public static function rotate(): void
    {
        Session::set('_csrf', bin2hex(random_bytes(32)));
    }
}
