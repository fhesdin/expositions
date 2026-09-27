<?php

namespace App\Artists;

use Core\Database;
use Core\Model;

class ArtistModel extends Model
{
    protected static string $table = 'artists';

    public static function find(int|string $id): ?array
    {
        $row = Database::run(
            'SELECT a.*, c.nom AS commune_name
             FROM artists a LEFT JOIN communes c ON c.id = a.commune_id
             WHERE a.id = ? LIMIT 1',
            [$id]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function bySlug(string $slug): ?array
    {
        $row = Database::run(
            'SELECT a.*, c.nom AS commune_name
             FROM artists a LEFT JOIN communes c ON c.id = a.commune_id
             WHERE a.slug = ? LIMIT 1',
            [$slug]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function forUser(int $userId): ?array
    {
        $row = Database::run('SELECT * FROM artists WHERE user_id = ? LIMIT 1', [$userId])->fetch();
        return $row === false ? null : $row;
    }

    public static function directory(array $filters = []): array
    {
        $sql = 'SELECT a.*, c.nom AS commune_name
                FROM artists a LEFT JOIN communes c ON c.id = a.commune_id
                WHERE a.status = "published"';
        $params = [];
        if (!empty($filters['commune'])) {
            $sql .= ' AND a.commune_id = ?';
            $params[] = (int) $filters['commune'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (a.name LIKE ? OR a.disciplines LIKE ? OR a.bio LIKE ?)';
            $q = '%' . $filters['q'] . '%';
            array_push($params, $q, $q, $q);
        }
        $sql .= ' ORDER BY a.name';
        return Database::run($sql, $params)->fetchAll();
    }

    public static function works(int $artistId): array
    {
        return Database::run(
            'SELECT * FROM artist_works WHERE artist_id = ? ORDER BY position, id',
            [$artistId]
        )->fetchAll();
    }

    public static function replaceWorks(int $artistId, array $works): void
    {
        Database::run('DELETE FROM artist_works WHERE artist_id = ?', [$artistId]);
        $pos = 0;
        foreach ($works as $w) {
            $path = trim((string) ($w['image_path'] ?? ''));
            if ($path === '') {
                continue;
            }
            Database::run(
                'INSERT INTO artist_works (artist_id, title, year, technique, dimensions, image_path, caption, position)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$artistId, $w['title'] ?? '', $w['year'] ?? null, $w['technique'] ?? null,
                 $w['dimensions'] ?? null, $path, $w['caption'] ?? null, $pos++]
            );
        }
    }
}
