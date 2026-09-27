<?php

namespace App\Models;

use Core\Database;

/**
 * Jetons de réinitialisation de mot de passe (valables 2 h, usage unique).
 * Table créée à la volée si absente (pas de migration dédiée nécessaire
 * pour ce mécanisme interne).
 */
final class PasswordReset
{
    public static function table(): void
    {
        Database::run(
            'CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                consumed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_pr_token (token),
                KEY idx_pr_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public static function issue(int $userId, string $token): void
    {
        self::table();
        // invalider les anciens jetons du user
        Database::run('UPDATE password_resets SET consumed_at = NOW() WHERE user_id = ? AND consumed_at IS NULL', [$userId]);
        Database::run(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))',
            [$userId, $token]
        );
    }

    public static function findValid(string $token): ?array
    {
        self::table();
        $row = Database::run(
            'SELECT * FROM password_resets WHERE token = ? AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1',
            [$token]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function consume(int $id): void
    {
        Database::run('UPDATE password_resets SET consumed_at = NOW() WHERE id = ?', [$id]);
    }
}
