<?php

namespace App\Community;

use Core\Database;

/**
 * "Mon agenda" : expositions enregistrées par le membre, exportables en iCal.
 * (implémenté sur la table favorites kind=exhibition, avec dates pour iCal)
 */
final class AgendaModel
{
    public static function forUser(int $userId): array
    {
        return Database::run(
            'SELECT e.*, c.nom AS commune_name, p.name AS place_name, p.address AS place_address,
                    cat.color AS category_color, cat.name AS category_name
             FROM favorites f
             JOIN exhibitions e ON e.id = f.item_id AND f.kind = "exhibition"
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN places p ON p.id = e.place_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE f.user_id = ?
             ORDER BY COALESCE(e.starts_at, e.created_at) DESC',
            [$userId]
        )->fetchAll();
    }

    /** Événements (vernissages…) des expositions dans l'agenda du membre. */
    public static function upcomingEventsForUser(int $userId): array
    {
        return Database::run(
            'SELECT ev.*, e.title AS exhibition_title, e.slug AS exhibition_slug, p.name AS place_name
             FROM favorites f
             JOIN exhibition_events ev ON ev.exhibition_id = f.item_id AND f.kind = "exhibition"
             JOIN exhibitions e ON e.id = ev.exhibition_id
             LEFT JOIN places p ON p.id = ev.place_id
             WHERE f.user_id = ? AND ev.status = "published" AND ev.starts_at >= NOW()
             ORDER BY ev.starts_at LIMIT 50',
            [$userId]
        )->fetchAll();
    }
}
