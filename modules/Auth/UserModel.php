<?php

namespace App\Auth;

use Core\Database;
use Core\Model;

class UserModel extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', $username);
    }

    private static function findBy(string $field, string $value): ?array
    {
        $row = Database::run(
            "SELECT u.*, r.name AS role_name, r.rank AS role_rank
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.$field = ? LIMIT 1",
            [$value]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function find(int|string $id): ?array
    {
        $row = Database::run(
            'SELECT u.*, r.name AS role_name, r.rank AS role_rank,
                    EXISTS(SELECT 1 FROM place_managers pm
                           JOIN places p ON p.id = pm.place_id
                           WHERE pm.user_id = u.id AND p.verified = 1) AS has_verified_place
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1',
            [$id]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function create(array $data): int|string
    {
        $id = parent::create($data);
        return $id;
    }

    public static function touchLogin(int $id): void
    {
        Database::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [$email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = ?';
        $params = [$username];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    public static function setConfirmToken(int $id, string $token): void
    {
        Database::run('UPDATE users SET confirm_token = ? WHERE id = ?', [$token, $id]);
    }

    public static function confirmEmail(string $token): bool
    {
        $row = Database::run('SELECT id FROM users WHERE confirm_token = ? LIMIT 1', [$token])->fetch();
        if ($row === false) {
            return false;
        }
        Database::run('UPDATE users SET email_confirmed_at = NOW(), confirm_token = NULL WHERE id = ?', [$row['id']]);
        return true;
    }

    public static function paginate(int $perPage, int $offset): array
    {
        return Database::run(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY u.id LIMIT ? OFFSET ?',
            [$perPage, $offset]
        )->fetchAll();
    }

    // ---------------- remember-me ----------------

    public static function storeRememberToken(int $id, string $selector, string $validatorHash, \DateTimeInterface $expires): void
    {
        Database::run(
            'UPDATE users SET remember_selector = ?, remember_validator = ?, remember_expires_at = ? WHERE id = ?',
            [$selector, $validatorHash, $expires->format('Y-m-d H:i:s'), $id]
        );
    }

    public static function findByRememberSelector(string $selector): ?array
    {
        $row = Database::run(
            'SELECT u.*, r.name AS role_name, r.rank AS role_rank FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.remember_selector = ? LIMIT 1',
            [$selector]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function clearRememberToken(int $id): void
    {
        Database::run(
            'UPDATE users SET remember_selector = NULL, remember_validator = NULL, remember_expires_at = NULL WHERE id = ?',
            [$id]
        );
    }
}