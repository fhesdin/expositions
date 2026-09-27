<?php

namespace Core;

use App\Auth\UserModel;

/**
 * Authentification : session, remember-me, rôle courant (rang cumulatif).
 * Rôles expositions.top : membre(2) < artiste/gestionnaire(3) < moderateur(4) < admin(5).
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        Session::start();

        $userId = Session::get('user_id');
        if ($userId !== null) {
            self::$user = UserModel::find((int) $userId);
            if (self::$user === null || !(bool) (self::$user['is_active'] ?? false)) {
                Session::remove('user_id');
                self::$user = null;
            }
            return self::$user;
        }

        // Remember-me
        $cookie = $_COOKIE['expo_remember'] ?? null;
        if (is_string($cookie) && $cookie !== '') {
            self::$user = self::validateRememberCookie($cookie);
            if (self::$user !== null) {
                Session::regenerate();
                Session::set('user_id', self::$user['id']);
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    /** Rang du rôle courant (1=visiteur, 5=admin). 0 si non connecté. */
    public static function rank(): int
    {
        $u = self::user();
        return $u ? (int) ($u['role_rank'] ?? 0) : 0;
    }

    public static function role(): string
    {
        $u = self::user();
        return $u ? ($u['role_name'] ?? 'visiteur') : 'visiteur';
    }

    public static function isAdmin(): bool
    {
        return self::minRank(5);
    }

    public static function isModerator(): bool
    {
        return self::minRank(4);
    }

    /** Contributeur = artiste OU gestionnaire de lieu (rang 3) OU au-dessus. */
    public static function isContributor(): bool
    {
        return self::minRank(3);
    }

    /** Peut-il publier sans modération préalable ? (niveau de confiance confirmé) */
    public static function isTrusted(): bool
    {
        $u = self::user();
        if ($u === null) {
            return false;
        }
        if (self::minRank(4)) {
            return true;
        }
        // Lieu institutionnel vérifié = gestionnaire d'un lieu verified
        return (bool) ($u['has_verified_place'] ?? false)
            || (bool) ($u['artist_verified'] ?? false);
    }

    public static function minRank(int $rank): bool
    {
        return self::rank() >= $rank;
    }

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = UserModel::findByEmail($email)
            ?? UserModel::findByUsername($email);
        if ($user === null || !(bool) $user['is_active']) {
            return false;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }
        self::login($user, $remember);
        return true;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::start();
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Csrf::rotate();
        UserModel::touchLogin((int) $user['id']);

        if ($remember) {
            self::setRememberCookie((int) $user['id']);
        }
        self::$user = UserModel::find((int) $user['id']);
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::start();
        $userId = Session::get('user_id');
        if ($userId !== null) {
            UserModel::clearRememberToken((int) $userId);
        }
        Session::destroy();
        setcookie('expo_remember', '', time() - 3600, '/', '', true, true);
        self::$user = null;
        self::$loaded = true;
    }

    // ------------------------------------------------------------------
    // Remember-me : sélecteur + validateur haché (pas de token brut en base)
    // ------------------------------------------------------------------

    private static function setRememberCookie(int $userId): void
    {
        $days = Env::getInt('REMEMBER_ME_DAYS', 30);
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $expires = new \DateTime("+$days days");

        UserModel::storeRememberToken($userId, $selector, hash('sha256', $validator), $expires);

        setcookie(
            'expo_remember',
            $selector . ':' . $validator,
            ['expires' => $expires->getTimestamp(), 'path' => '/', 'secure' => !Env::getBool('APP_DEBUG'), 'httponly' => true, 'samesite' => 'Lax']
        );
    }

    private static function validateRememberCookie(string $cookie): ?array
    {
        $parts = explode(':', $cookie);
        if (count($parts) !== 2) {
            return null;
        }
        [$selector, $validator] = $parts;
        $user = UserModel::findByRememberSelector($selector);
        if ($user === null || !password_verify($validator, (string) $user['remember_validator'])) {
            return null;
        }
        if (strtotime((string) $user['remember_expires_at']) < time()) {
            UserModel::clearRememberToken((int) $user['id']);
            return null;
        }
        return $user;
    }
}
