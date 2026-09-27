<?php

namespace Core;

/**
 * Chargement de la configuration globale (.env + table settings).
 */
final class Config
{
    private static array $cache = [];

    public static function load(): void
    {
        $envPath = dirname(__DIR__) . '/.env';
        Env::load($envPath);

        if (Env::get('APP_TIMEZONE')) {
            date_default_timezone_set(Env::get('APP_TIMEZONE'));
        }

        if (Env::getBool('APP_DEBUG')) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
            ini_set('display_errors', '0');
        }
    }

    /** Récupère une valeur depuis la table settings (avec cache mémoire). */
    public static function setting(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $row = Database::run('SELECT value FROM settings WHERE `key` = ? LIMIT 1', [$key])->fetch();
        $val = $row === false ? $default : $row['value'];
        self::$cache[$key] = $val;
        return $val;
    }

    public static function setSetting(string $key, mixed $value): void
    {
        Database::run(
            'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, (string) $value]
        );
        self::$cache[$key] = $value;
    }
}
