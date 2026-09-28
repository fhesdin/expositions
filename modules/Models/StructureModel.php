<?php

namespace App\Models;

use Core\Database;

final class StructureModel
{
    public const TYPES = [
        'association' => 'Association',
        'musee' => 'Musée',
        'collectif' => 'Collectif d\'artistes',
        'mairie' => 'Mairie / service culturel',
        'galerie' => 'Galerie',
        'autre' => 'Autre',
    ];

    /** Création (contributor+) — retourne l'id. */
    public static function create(array $data, int $userId): int
    {
        Database::run(
            'INSERT INTO structures (slug, nom, type, description, website, email, phone, commune_id, status, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,? ,NOW())',
            [
                $data['slug'], $data['nom'], $data['type'], $data['description'] ?: null,
                $data['website'] ?: null, $data['email'] ?: null, $data['phone'] ?: null,
                $data['commune_id'] ?: null, $data['status'], $userId,
            ]
        );
        $id = (int) Database::lastInsertId();
        // le créateur devient admin de sa structure
        if ($data['status'] === 'published') {
            Database::run(
                'INSERT INTO structure_members (structure_id, user_id, role, status, requested_by, joined_at)
                 VALUES (?,?,?,"active","user",NOW())',
                [$id, $userId, 'admin']
            );
        }
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE structures SET nom=?, type=?, description=?, website=?, email=?, phone=?, commune_id=?, updated_at=NOW() WHERE id=?',
            [$data['nom'], $data['type'], $data['description'] ?: null, $data['website'] ?: null,
             $data['email'] ?: null, $data['phone'] ?: null, $data['commune_id'] ?: null, $id]
        );
    }

    public static function bySlug(string $slug): ?array
    {
        $row = Database::run(
            'SELECT s.*, c.nom AS commune_name, (SELECT COUNT(*) FROM places p WHERE p.structure_id = s.id) AS places_count,
                    (SELECT COUNT(*) FROM exhibitions e WHERE e.structure_id = s.id AND e.status = "published") AS expos_count
             FROM structures s LEFT JOIN communes c ON c.id = s.commune_id WHERE s.slug = ?',
            [$slug]
        )->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $row = Database::run('SELECT * FROM structures WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    /** Annuaire public : structures publiées. */
    public static function listPublished(?string $type = null, string $q = ''): array
    {
        $sql = 'SELECT s.*, c.nom AS commune_name,
                (SELECT COUNT(*) FROM places p WHERE p.structure_id = s.id) AS places_count,
                (SELECT COUNT(*) FROM exhibitions e WHERE e.structure_id = s.id AND e.status = "published") AS expos_count
                FROM structures s LEFT JOIN communes c ON c.id = s.commune_id
                WHERE s.status = "published"';
        $params = [];
        if ($type) {
            $sql .= ' AND s.type = ?';
            $params[] = $type;
        }
        if ($q !== '') {
            $sql .= ' AND (s.nom LIKE ? OR s.description LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        $sql .= ' ORDER BY s.nom ASC LIMIT 100';
        return Database::run($sql, $params)->fetchAll();
    }

    /** Structures en attente de modération. */
    public static function listPending(): array
    {
        return Database::run(
            'SELECT s.*, u.username AS creator, c.nom AS commune_name FROM structures s
             LEFT JOIN users u ON u.id = s.created_by LEFT JOIN communes c ON c.id = s.commune_id
             WHERE s.status = "pending" ORDER BY s.created_at ASC LIMIT 50'
        )->fetchAll();
    }

    /** Rôle de l'utilisateur dans la structure : null si aucun. */
    public static function memberRole(int $structureId, int $userId): ?string
    {
        $role = Database::run(
            'SELECT role FROM structure_members WHERE structure_id = ? AND user_id = ? AND status = "active"',
            [$structureId, $userId]
        )->fetchColumn();
        return $role !== false ? (string) $role : null;
    }

    /** Toutes les structures actives d'un utilisateur (pour /mon-espace). */
    public static function ofUser(int $userId): array
    {
        return Database::run(
            'SELECT s.*, m.role, m.status AS member_status, m.requested_by
             FROM structure_members m JOIN structures s ON s.id = m.structure_id
             WHERE m.user_id = ? AND m.status IN ("active","pending")
             ORDER BY s.nom ASC',
            [$userId]
        )->fetchAll();
    }

    public static function members(int $structureId): array
    {
        return Database::run(
            'SELECT m.*, u.username, u.email FROM structure_members m JOIN users u ON u.id = m.user_id
             WHERE m.structure_id = ? ORDER BY FIELD(m.status, "pending","active","refused"), m.role DESC, u.username ASC',
            [$structureId]
        )->fetchAll();
    }

    public static function setMember(int $structureId, int $userId, string $role, string $status, string $requestedBy): void
    {
        Database::run(
            'INSERT INTO structure_members (structure_id, user_id, role, status, requested_by, joined_at)
             VALUES (?,?,?,?,?,IF(?="active",NOW(),NULL))
             ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status),
                joined_at = IF(VALUES(status)="active" AND joined_at IS NULL, NOW(), joined_at)',
            [$structureId, $userId, $role, $status, $requestedBy, $status]
        );
    }

    public static function removeMember(int $structureId, int $userId): void
    {
        Database::run('DELETE FROM structure_members WHERE structure_id = ? AND user_id = ?', [$structureId, $userId]);
    }

    public static function uniqueSlug(string $base): string
    {
        $base = \slugify($base);
        if ($base === '') {
            $base = 'structure';
        }
        $slug = $base;
        $i = 2;
        while (Database::run('SELECT id FROM structures WHERE slug = ?', [$slug])->fetch()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::run('UPDATE structures SET status = ? WHERE id = ?', [$status, $id]);
    }
}
