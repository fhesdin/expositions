<?php

namespace App\Community;

use Core\Database;

final class FavoriteModel
{
    public static function has(int $userId, string $kind, int $itemId): bool
    {
        return (bool) Database::run(
            'SELECT COUNT(*) FROM favorites WHERE user_id = ? AND kind = ? AND item_id = ?',
            [$userId, $kind, $itemId]
        )->fetchColumn();
    }

    public static function toggle(int $userId, string $kind, int $itemId): bool
    {
        if (self::has($userId, $kind, $itemId)) {
            Database::run('DELETE FROM favorites WHERE user_id = ? AND kind = ? AND item_id = ?', [$userId, $kind, $itemId]);
            return false;
        }
        Database::run('INSERT INTO favorites (user_id, kind, item_id) VALUES (?, ?, ?)', [$userId, $kind, $itemId]);
        return true;
    }

    /** Favoris enrichis (titre + slug selon le type) pour le profil. */
    public static function forUser(int $userId): array
    {
        $rows = Database::run(
            'SELECT f.kind, f.item_id, f.created_at FROM favorites f WHERE f.user_id = ? ORDER BY f.created_at DESC',
            [$userId]
        )->fetchAll();
        foreach ($rows as &$r) {
            switch ($r['kind']) {
                case 'exhibition':
                    $e = Database::run('SELECT title, slug FROM exhibitions WHERE id = ?', [$r['item_id']])->fetch();
                    $r['title'] = $e['title'] ?? '(fiche supprimée)';
                    $r['slug'] = $e['slug'] ?? null;
                    break;
                case 'artist':
                    $a = Database::run('SELECT name, slug FROM artists WHERE id = ?', [$r['item_id']])->fetch();
                    $r['title'] = $a['name'] ?? '(page supprimée)';
                    $r['slug'] = $a['slug'] ?? null;
                    break;
                case 'place':
                    $p = Database::run('SELECT name, slug FROM places WHERE id = ?', [$r['item_id']])->fetch();
                    $r['title'] = $p['name'] ?? '(lieu supprimé)';
                    $r['slug'] = $p['slug'] ?? null;
                    break;
            }
        }
        return $rows;
    }
}
