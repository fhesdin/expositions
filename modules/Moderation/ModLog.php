<?php

namespace App\Moderation;

use Core\Database;

/**
 * Journal de toutes les actions de modération (qui, quoi, quand).
 */
final class ModLog
{
    public static function log(string $action, string $kind, ?int $itemId, ?string $details = null): void
    {
        try {
            Database::run(
                'INSERT INTO mod_actions (user_id, action, kind, item_id, details) VALUES (?, ?, ?, ?, ?)',
                [\Core\Auth::id() ?? 0, $action, $kind, $itemId, mb_substr((string) $details, 0, 500)]
            );
        } catch (\Throwable) {
            // le journal ne doit jamais bloquer l'action métier
        }
    }

    public static function recent(int $limit = 50): array
    {
        return Database::run(
            'SELECT m.*, u.username FROM mod_actions m LEFT JOIN users u ON u.id = m.user_id
             ORDER BY m.created_at DESC LIMIT ' . (int) $limit
        )->fetchAll();
    }
}
