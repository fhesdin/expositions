<?php

namespace App\Expositions;

use Core\Database;
use Core\Model;

class ExhibitionModel extends Model
{
    protected static string $table = 'exhibitions';

    /** Statuts visibles publiquement (liste + fiches). */
    public const PUBLIC_STATUSES = ['published', 'archived', 'cancelled', 'postponed'];
    /** Statuts actifs par défaut (archives et annulées exclus). */
    public const ACTIVE_STATUSES = ['published'];

    // ------------------------------------------------------------ fetch

    public static function find(int|string $id): ?array
    {
        $row = Database::run(
            'SELECT e.*, p.name AS place_name, p.slug AS place_slug, p.address AS place_address,
                    p.latitude AS place_lat, p.longitude AS place_lng, p.opening_hours AS place_hours,
                    p.access_pmr AS place_pmr, p.free_access AS place_free, p.website AS place_website,
                    p.phone AS place_phone, p.email AS place_email, c.nom AS commune_name,
                    cat.name AS category_name, cat.color AS category_color, cat.slug AS category_slug,
                    cat.icon AS category_icon, l.name AS label_name
             FROM exhibitions e
             LEFT JOIN places p ON p.id = e.place_id
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             LEFT JOIN labels l ON l.id = e.label_id
             WHERE e.id = ? LIMIT 1',
            [$id]
        )->fetch();
        return $row === false ? null : $row;
    }

    public static function bySlug(string $slug): ?array
    {
        $row = Database::run('SELECT id FROM exhibitions WHERE slug = ? LIMIT 1', [$slug])->fetch();
        return $row === false ? null : self::find($row['id']);
    }

    public static function artists(int $id): array
    {
        return Database::run(
            'SELECT ea.artist_id, ea.free_name, a.name, a.slug, a.disciplines
             FROM exhibition_artists ea
             LEFT JOIN artists a ON a.id = ea.artist_id
             WHERE ea.exhibition_id = ? ORDER BY COALESCE(a.name, ea.free_name)',
            [$id]
        )->fetchAll();
    }

    public static function tags(int $id): array
    {
        return Database::run(
            'SELECT t.* FROM exhibition_tags et JOIN tags t ON t.id = et.tag_id
             WHERE et.exhibition_id = ? ORDER BY t.name',
            [$id]
        )->fetchAll();
    }

    public static function publicsIds(int $id): array
    {
        return Database::run(
            'SELECT public_id FROM exhibition_publics WHERE exhibition_id = ?',
            [$id]
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function images(int $id): array
    {
        return Database::run(
            'SELECT * FROM exhibition_images WHERE exhibition_id = ? ORDER BY position, id',
            [$id]
        )->fetchAll();
    }

    public static function docs(int $id): array
    {
        return Database::run(
            'SELECT * FROM exhibition_docs WHERE exhibition_id = ? ORDER BY kind',
            [$id]
        )->fetchAll();
    }

    public static function events(int $id, bool $onlyUpcoming = false): array
    {
        $sql = 'SELECT ev.*, p.name AS place_name FROM exhibition_events ev
                LEFT JOIN places p ON p.id = ev.place_id
                WHERE ev.exhibition_id = ?';
        if ($onlyUpcoming) {
            $sql .= ' AND ev.starts_at >= NOW()';
        }
        $sql .= ' ORDER BY ev.starts_at';
        return Database::run($sql, [$id])->fetchAll();
    }

    // ------------------------------------------------------------ listes & filtres

    /**
     * Filtres combinables : q, category, commune, tag, type (lieu), free,
     * pmr, public, period (aujourd'hui|week-end|semaine|mois|range), d1, d2,
     * scope (active|archives|all), open_sunday.
     */
    public static function search(array $f, int $perPage = 12, int $page = 1, bool &$total = null): array
    {
        $where = [];
        $params = [];

        $scope = $f['scope'] ?? 'active';
        if ($scope === 'active') {
            $where[] = 'e.status IN ("published","cancelled","postponed")';
        } elseif ($scope === 'archives') {
            $where[] = 'e.status = "archived"';
        } else {
            $where[] = 'e.status IN ("published","archived","cancelled","postponed")';
        }

        if (!empty($f['q'])) {
            $q = '%' . $f['q'] . '%';
            $where[] = '(e.title LIKE ? OR e.summary LIKE ? OR e.description LIKE ? OR p.name LIKE ? OR c.nom LIKE ? OR a.name LIKE ? OR ea.free_name LIKE ? OR t.name LIKE ?)';
            array_push($params, $q, $q, $q, $q, $q, $q, $q, $q);
        }
        if (!empty($f['category'])) {
            $where[] = 'cat.slug = ?';
            $params[] = $f['category'];
        }
        if (!empty($f['commune'])) {
            $where[] = 'e.commune_id = ?';
            $params[] = (int) $f['commune'];
        }
        if (!empty($f['tag'])) {
            $where[] = 'e.id IN (SELECT et.exhibition_id FROM exhibition_tags et JOIN tags t ON t.id = et.tag_id WHERE t.slug = ?)';
            $params[] = $f['tag'];
        }
        if (!empty($f['type'])) {
            $where[] = 'p.type_id = (SELECT id FROM place_types WHERE slug = ?)';
            $params[] = $f['type'];
        }
        if (!empty($f['free'])) {
            $where[] = '(e.price_kind = "gratuit" OR p.free_access = 1)';
        }
        if (!empty($f['pmr'])) {
            $where[] = '(e.access_pmr = 1 OR p.access_pmr = 1)';
        }
        if (!empty($f['public'])) {
            $where[] = 'e.id IN (SELECT exhibition_id FROM exhibition_publics WHERE public_id = (SELECT id FROM publics WHERE slug = ?))';
            $params[] = $f['public'];
        }
        if (!empty($f['label'])) {
            $where[] = 'e.label_id = (SELECT id FROM labels WHERE slug = ?)';
            $params[] = $f['label'];
        }

        // Périodes
        $today = date('Y-m-d');
        switch ($f['period'] ?? '') {
            case 'aujourdhui':
                $where[] = 'e.is_permanent = 0 AND e.starts_at <= ? AND (e.ends_at IS NULL OR e.ends_at >= ?)';
                array_push($params, $today, $today);
                break;
            case 'week-end':
                $sat = date('Y-m-d', strtotime('saturday this week'));
                $sun = date('Y-m-d', strtotime('sunday this week'));
                $where[] = 'e.is_permanent = 0 AND e.starts_at <= ? AND (e.ends_at IS NULL OR e.ends_at >= ?)';
                array_push($params, $sun, $sat);
                break;
            case 'semaine':
                $end = date('Y-m-d', strtotime('+7 days'));
                $where[] = 'e.is_permanent = 0 AND e.starts_at <= ? AND (e.ends_at IS NULL OR e.ends_at >= ?)';
                array_push($params, $end, $today);
                break;
            case 'mois':
                $end = date('Y-m-d', strtotime('+30 days'));
                $where[] = 'e.is_permanent = 0 AND e.starts_at <= ? AND (e.ends_at IS NULL OR e.ends_at >= ?)';
                array_push($params, $end, $today);
                break;
            case 'range':
                if (!empty($f['d1'])) {
                    $where[] = '(e.ends_at IS NULL OR e.ends_at >= ?)';
                    $params[] = $f['d1'];
                }
                if (!empty($f['d2'])) {
                    $where[] = '(e.is_permanent = 0 AND e.starts_at <= ?)';
                    $params[] = $f['d2'];
                }
                break;
        }
        if (!empty($f['open_sunday'])) {
            $where[] = '(p.opening_hours IS NULL OR p.opening_hours NOT LIKE "%lundi%")';
        }

        $from = 'FROM exhibitions e
                 LEFT JOIN places p ON p.id = e.place_id
                 LEFT JOIN communes c ON c.id = e.commune_id
                 LEFT JOIN categories cat ON cat.id = e.category_id
                 LEFT JOIN exhibition_artists ea ON ea.exhibition_id = e.id
                 LEFT JOIN artists a ON a.id = ea.artist_id
                 LEFT JOIN exhibition_tags etq ON etq.exhibition_id = e.id
                 LEFT JOIN tags t ON t.id = etq.tag_id';

        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        // total
        $total = (int) Database::run(
            'SELECT COUNT(DISTINCT e.id) ' . $from . $whereSql,
            $params
        )->fetchColumn();

        $order = match ($f['sort'] ?? 'pertinence') {
            'debut' => 'e.starts_at IS NULL, e.starts_at ASC',
            'fin' => 'e.ends_at IS NULL, e.ends_at ASC',
            'nouveautes' => 'e.created_at DESC',
            default => 'e.featured DESC, e.featured_order ASC, e.starts_at ASC',
        };

        $offset = max(0, ($page - 1) * $perPage);
        return Database::run(
            'SELECT DISTINCT e.*, p.name AS place_name, p.slug AS place_slug,
                    p.latitude AS place_lat, p.longitude AS place_lng, c.nom AS commune_name,
                    cat.name AS category_name, cat.color AS category_color, cat.slug AS category_slug, cat.icon AS category_icon
             ' . $from . $whereSql . '
             ORDER BY ' . $order . '
             LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();
    }

    // ------------------------------------------------------------ blocs accueil

    public static function featured(int $limit = 6): array
    {
        return Database::run(
            'SELECT e.*, c.nom AS commune_name, cat.color AS category_color, cat.name AS category_name
             FROM exhibitions e
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.status = "published" AND e.featured = 1
             ORDER BY e.featured_order LIMIT ' . (int) $limit
        )->fetchAll();
    }

    public static function weekend(int $limit = 8): array
    {
        $sat = date('Y-m-d', strtotime('saturday this week'));
        $sun = date('Y-m-d', strtotime('sunday this week'));
        return self::runningBetween($sat, $sun, $limit);
    }

    public static function vernissages(int $limit = 8): array
    {
        return Database::run(
            'SELECT ev.*, e.title AS exhibition_title, e.slug AS exhibition_slug, e.id AS exhibition_id,
                    p.name AS place_name, c.nom AS commune_name
             FROM exhibition_events ev
             JOIN exhibitions e ON e.id = ev.exhibition_id
             LEFT JOIN places p ON p.id = ev.place_id
             LEFT JOIN communes c ON c.id = e.commune_id
             WHERE ev.type = "vernissage" AND ev.status = "published" AND ev.starts_at >= NOW()
             ORDER BY ev.starts_at LIMIT ' . (int) $limit
        )->fetchAll();
    }

    public static function lastChance(int $days = 10, int $limit = 8): array
    {
        $limit_date = date('Y-m-d', strtotime("+$days days"));
        return Database::run(
            'SELECT e.*, c.nom AS commune_name, cat.color AS category_color, cat.name AS category_name
             FROM exhibitions e
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.status = "published" AND e.is_permanent = 0
               AND e.ends_at IS NOT NULL AND e.ends_at >= CURDATE() AND e.ends_at <= ?
             ORDER BY e.ends_at LIMIT ' . (int) $limit,
            [$limit_date]
        )->fetchAll();
    }

    public static function newest(int $limit = 8): array
    {
        return Database::run(
            'SELECT e.*, c.nom AS commune_name, cat.color AS category_color, cat.name AS category_name
             FROM exhibitions e
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.status = "published"
             ORDER BY e.created_at DESC LIMIT ' . (int) $limit
        )->fetchAll();
    }

    public static function ongoingCount(): int
    {
        return (int) Database::run(
            'SELECT COUNT(*) FROM exhibitions WHERE status = "published"
             AND (is_permanent = 1 OR (starts_at <= CURDATE() AND (ends_at IS NULL OR ends_at >= CURDATE())))'
        )->fetchColumn();
    }

    /** Dates lisibles en français (sans ext-intl) : "2 – 20 oct. 2026", "Permanente", "Dès le 2 oct. 2026". */
    public static function datesHuman(array $e): string
    {
        $months = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        if (!empty($e['is_permanent'])) {
            return 'Permanente';
        }
        if (empty($e['starts_at'])) {
            return 'Dates à venir';
        }
        $fmt = static function (string $d) use ($months): string {
            $ts = strtotime($d);
            if ($ts === false) {
                return '';
            }
            $day = (int) date('j', $ts);
            $mo = (int) date('n', $ts);
            $yr = (int) date('Y', $ts);
            return $day . ' ' . $months[$mo] . ($mo === 1 || $day === 1 ? '' : '') . ' ' . $yr;
        };
        $start = $fmt((string) $e['starts_at']);
        if (empty($e['ends_at'])) {
            return 'Dès le ' . $start;
        }
        $end = $fmt((string) $e['ends_at']);
        return $start === $end ? $start : $start . ' – ' . $end;
    }

    /** Expositions en cours sur une fenêtre [d1,d2] (calendrier / week-end). */
    public static function runningBetween(string $d1, string $d2, int $limit = 500): array
    {
        return Database::run(
            'SELECT e.*, p.name AS place_name, c.nom AS commune_name, cat.color AS category_color, cat.name AS category_name, cat.slug AS category_slug
             FROM exhibitions e
             LEFT JOIN places p ON p.id = e.place_id
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.status = "published" AND e.starts_at <= ? AND (e.ends_at IS NULL OR e.ends_at >= ?)
             ORDER BY e.starts_at LIMIT ' . (int) $limit,
            [$d2, $d1]
        )->fetchAll();
    }

    /** Vue calendrier d'un mois : renvoie [Y-m-d => [exhibitions]] + événements. */
    public static function calendarMonth(int $year, int $month): array
    {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', strtotime($first));
        $exhibitions = self::runningBetween($first, $last, 500);
        $events = Database::run(
            'SELECT ev.*, e.title AS exhibition_title, e.slug AS exhibition_slug
             FROM exhibition_events ev JOIN exhibitions e ON e.id = ev.exhibition_id
             WHERE ev.status = "published" AND ev.starts_at BETWEEN ? AND ? + INTERVAL 1 DAY - INTERVAL 1 SECOND
             ORDER BY ev.starts_at',
            [$first . ' 00:00:00', $last . ' 23:59:59']
        )->fetchAll();
        return [$exhibitions, $events];
    }

    // ------------------------------------------------------------ suggestions & relations

    public static function samePlace(int $placeId, int $exceptId, int $limit = 4): array
    {
        return Database::run(
            'SELECT e.*, c.nom AS commune_name, cat.color AS category_color FROM exhibitions e
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.place_id = ? AND e.id <> ? AND e.status = "published"
             ORDER BY e.starts_at DESC LIMIT ' . (int) $limit,
            [$placeId, $exceptId]
        )->fetchAll();
    }

    public static function sameArtists(array $artistIds, int $exceptId, int $limit = 4): array
    {
        $ids = array_values(array_filter(array_map('intval', $artistIds)));
        if ($ids === []) {
            return [];
        }
        $ph = implode(',', $ids);
        return Database::run(
            "SELECT DISTINCT e.*, c.nom AS commune_name, cat.color AS category_color
             FROM exhibitions e
             JOIN exhibition_artists ea ON ea.exhibition_id = e.id AND ea.artist_id IN ($ph)
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.id <> ? AND e.status = 'published'
             ORDER BY e.starts_at DESC LIMIT " . (int) $limit,
            [$exceptId]
        )->fetchAll();
    }

    // ------------------------------------------------------------ statistiques

    public static function statsByMonth(int $months = 12): array
    {
        return Database::run(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") ym, COUNT(*) n
             FROM exhibitions WHERE status IN ("published","archived")
             GROUP BY ym ORDER BY ym DESC LIMIT ' . (int) $months
        )->fetchAll();
    }

    public static function statsByCategory(): array
    {
        return Database::run(
            'SELECT cat.name, COUNT(e.id) n FROM categories cat
             LEFT JOIN exhibitions e ON e.category_id = cat.id AND e.status IN ("published","archived")
             GROUP BY cat.id ORDER BY n DESC, cat.name'
        )->fetchAll();
    }

    public static function statsByCommune(int $limit = 15): array
    {
        return Database::run(
            'SELECT c.nom, COUNT(e.id) n FROM communes c
             LEFT JOIN exhibitions e ON e.commune_id = c.id AND e.status IN ("published","archived")
             GROUP BY c.id HAVING n > 0 ORDER BY n DESC, c.nom LIMIT ' . (int) $limit
        )->fetchAll();
    }

    /** Doublons potentiels : même lieu + chevauchement de dates + titre proche. */
    public static function findDuplicateCandidate(int $placeId, string $title, ?string $d1, ?string $d2): ?array
    {
        if ($placeId <= 0) {
            return null;
        }
        $rows = Database::run(
            'SELECT id, title FROM exhibitions
             WHERE place_id = ? AND status IN ("pending","published")
             AND (is_permanent = 1 OR (starts_at <= COALESCE(?, CURDATE() + INTERVAL 365 DAY) AND (ends_at IS NULL OR ends_at >= COALESCE(?, CURDATE()))))
             LIMIT 20',
            [$placeId, $d2, $d1]
        )->fetchAll();
        foreach ($rows as $r) {
            similar_text(mb_strtolower($r['title']), mb_strtolower($title), $pct);
            if ($pct > 80.0) {
                return $r;
            }
        }
        return null;
    }

    public static function incrementView(int $id): void
    {
        Database::run('UPDATE exhibitions SET view_count = view_count + 1 WHERE id = ?', [$id]);
    }

    /** Passage en archives : fini depuis hier. Appelé par scripts/archive_ended.php */
    public static function archiveEnded(): int
    {
        $stmt = Database::run(
            'UPDATE exhibitions SET status = "archived"
             WHERE status = "published" AND is_permanent = 0 AND ends_at IS NOT NULL AND ends_at < CURDATE()'
        );
        $stmt2 = Database::run(
            'UPDATE exhibition_events SET status = "archived"
             WHERE status = "published" AND starts_at IS NOT NULL AND starts_at < NOW() - INTERVAL 1 DAY'
        );
        return $stmt->rowCount() + $stmt2->rowCount();
    }
}
