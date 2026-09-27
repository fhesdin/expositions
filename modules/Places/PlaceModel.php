<?php

namespace App\Places;

use Core\Database;
use Core\Model;

class PlaceModel extends Model
{
    protected static string $table = 'places';

    public const STATUSES = ['draft', 'pending', 'published', 'rejected', 'hidden'];

    public static function find(int|string $id): ?array
    {
        $row = Database::run(
            'SELECT p.*, t.name AS type_name, t.slug AS type_slug, c.nom AS commune_name,
                    c.latitude AS commune_lat, c.longitude AS commune_lng
             FROM places p
             LEFT JOIN place_types t ON t.id = p.type_id
             LEFT JOIN communes c ON c.id = p.commune_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function bySlug(string $slug): ?array
    {
        $row = Database::run(
            'SELECT p.*, t.name AS type_name, c.nom AS commune_name
             FROM places p
             LEFT JOIN place_types t ON t.id = p.type_id
             LEFT JOIN communes c ON c.id = p.commune_id
             WHERE p.slug = ? LIMIT 1',
            [$slug]
        )->fetch();
        return $row === false ? null : $row;
    }

    /** Annuaires : lieux publiés, filtres type/commune/q. */
    public static function directory(array $filters = []): array
    {
        $sql = 'SELECT p.*, t.name AS type_name, c.nom AS commune_name
                FROM places p
                LEFT JOIN place_types t ON t.id = p.type_id
                LEFT JOIN communes c ON c.id = p.commune_id
                WHERE p.status = "published"';
        $params = [];
        if (!empty($filters['type'])) {
            $sql .= ' AND t.slug = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['commune'])) {
            $sql .= ' AND p.commune_id = ?';
            $params[] = (int) $filters['commune'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (p.name LIKE ? OR p.description LIKE ? OR c.nom LIKE ?)';
            $q = '%' . $filters['q'] . '%';
            array_push($params, $q, $q, $q);
        }
        $sql .= ' ORDER BY p.name';
        return Database::run($sql, $params)->fetchAll();
    }

    /** Lieux gérés par un utilisateur (gestionnaire). */
    public static function managedBy(int $userId): array
    {
        return Database::run(
            'SELECT p.*, pm.is_primary FROM places p
             JOIN place_managers pm ON pm.place_id = p.id
             WHERE pm.user_id = ? ORDER BY p.name',
            [$userId]
        )->fetchAll();
    }

    public static function isManager(int $placeId, int $userId): bool
    {
        return (bool) Database::run(
            'SELECT COUNT(*) FROM place_managers WHERE place_id = ? AND user_id = ?',
            [$placeId, $userId]
        )->fetchColumn();
    }

    public static function managers(int $placeId): array
    {
        return Database::run(
            'SELECT u.id, u.username, pm.is_primary FROM place_managers pm
             JOIN users u ON u.id = pm.user_id WHERE pm.place_id = ? ORDER BY pm.is_primary DESC, u.username',
            [$placeId]
        )->fetchAll();
    }

    public static function setManagers(int $placeId, array $userIds, int $primaryId = 0): void
    {
        Database::run('DELETE FROM place_managers WHERE place_id = ?', [$placeId]);
        $seen = [];
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0 || isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            Database::run(
                'INSERT INTO place_managers (place_id, user_id, is_primary) VALUES (?, ?, ?)',
                [$placeId, $uid, ((int) $primaryId === $uid) ? 1 : 0]
            );
        }
    }

    /** Effectif d'expositions en cours par lieu (pour la carte et l'annuaire). */
    public static function withOngoingCounts(array $rows): array
    {
        foreach ($rows as &$p) {
            $p['ongoing_count'] = (int) Database::run(
                'SELECT COUNT(*) FROM exhibitions WHERE place_id = ? AND status = "published"
                 AND (is_permanent = 1 OR (starts_at <= CURDATE() AND (ends_at IS NULL OR ends_at >= CURDATE())))',
                [$p['id']]
            )->fetchColumn();
        }
        return $rows;
    }

    /** Prochaines ouvertures de lieux pour la carte (marqueurs). */
    public static function forMap(): array
    {
        $rows = Database::run(
            'SELECT p.id, p.slug, p.name, p.latitude, p.longitude, t.name AS type_name, c.nom AS commune_name,
                    (SELECT COUNT(*) FROM exhibitions e WHERE e.place_id = p.id AND e.status = "published"
                     AND (e.is_permanent = 1 OR (e.starts_at <= CURDATE() AND (e.ends_at IS NULL OR e.ends_at >= CURDATE())))) AS ongoing_count
             FROM places p
             LEFT JOIN place_types t ON t.id = p.type_id
             LEFT JOIN communes c ON c.id = p.commune_id
             WHERE p.status = "published" AND p.latitude IS NOT NULL AND p.longitude IS NOT NULL
             ORDER BY ongoing_count DESC, p.name'
        )->fetchAll();
        return $rows;
    }

    public static function types(): array
    {
        return Database::run('SELECT * FROM place_types ORDER BY name')->fetchAll();
    }
}
