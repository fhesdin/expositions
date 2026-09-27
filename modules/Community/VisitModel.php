<?php

namespace App\Community;

use Core\Database;

/** Compteurs "J'y vais" / "J'y suis allé". */
final class VisitModel
{
    public static function get(int $userId, int $exhibitionId): ?array
    {
        $row = Database::run(
            'SELECT * FROM visits WHERE user_id = ? AND exhibition_id = ?',
            [$userId, $exhibitionId]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function counts(int $exhibitionId): array
    {
        $row = Database::run(
            'SELECT COALESCE(SUM(going),0) AS going, COALESCE(SUM(went),0) AS went
             FROM visits WHERE exhibition_id = ?',
            [$exhibitionId]
        )->fetch();
        return ['going' => (int) $row['going'], 'went' => (int) $row['went']];
    }

    public static function toggle(int $userId, int $exhibitionId, string $which): void
    {
        $current = self::get($userId, $exhibitionId);
        $col = $which === 'went' ? 'went' : 'going';
        if ($current === null) {
            Database::run(
                "INSERT INTO visits (user_id, exhibition_id, going, went) VALUES (?, ?, ?, ?)",
                [$userId, $exhibitionId, $col === 'going' ? 1 : 0, $col === 'went' ? 1 : 0]
            );
        } else {
            $newVal = (int) $current[$col] === 1 ? 0 : 1;
            // passer de "j'y vais" à "j'y suis allé" automatiquement
            $other = $col === 'going' ? 'went' : 'going';
            $otherVal = ($newVal === 1 && $col === 'went') ? 0 : (int) $current[$other];
            if ($col === 'going' && $newVal === 1) {
                $otherVal = 0;
            }
            Database::run(
                "UPDATE visits SET $col = ?, $other = ? WHERE user_id = ? AND exhibition_id = ?",
                [$newVal, $otherVal, $userId, $exhibitionId]
            );
        }
    }
}
