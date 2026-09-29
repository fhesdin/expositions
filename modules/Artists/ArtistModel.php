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
            'SELECT a.*, c.nom AS commune_name,
                    (a.user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users u WHERE u.id = a.user_id AND u.artist_verified = 1)) AS artist_verified
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

    public static function disciplinesAll(): array
    {
        return Database::run('SELECT id, name FROM disciplines ORDER BY name')->fetchAll();
    }

    public static function disciplinesOf(int $artistId): array
    {
        return Database::run(
            'SELECT d.id, d.name, d.slug FROM artist_disciplines ad JOIN disciplines d ON d.id = ad.discipline_id WHERE ad.artist_id = ? ORDER BY d.name',
            [$artistId]
        )->fetchAll();
    }

    public static function disciplineNames(int $artistId): array
    {
        return array_column(self::disciplinesOf($artistId), 'name');
    }

    public static function directory(array $filters = []): array
    {
        $sql = 'SELECT a.*, c.nom AS commune_name,
                       (a.user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users u WHERE u.id = a.user_id AND u.artist_verified = 1)) AS artist_verified,
                       (SELECT GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ", ") FROM artist_disciplines ad JOIN disciplines d ON d.id = ad.discipline_id WHERE ad.artist_id = a.id) AS disciplines
                FROM artists a LEFT JOIN communes c ON c.id = a.commune_id
                WHERE a.status = "published"';
        $params = [];
        if (!empty($filters['commune'])) {
            $sql .= ' AND a.commune_id = ?';
            $params[] = (int) $filters['commune'];
        }
        if (!empty($filters['discipline'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM artist_disciplines ad3 WHERE ad3.artist_id = a.id AND ad3.discipline_id = ?)';
            $params[] = (int) $filters['discipline'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (a.name LIKE ? OR a.bio LIKE ? OR EXISTS (SELECT 1 FROM artist_disciplines ad2 JOIN disciplines d2 ON d2.id = ad2.discipline_id WHERE ad2.artist_id = a.id AND d2.name LIKE ?))';
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
