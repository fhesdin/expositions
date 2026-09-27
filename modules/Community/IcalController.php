<?php

namespace App\Community;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

/**
 * iCal : export d'une exposition, d'une recherche filtrée,
 * du "Mon agenda" d'un membre + flux d'abonnement par token.
 */
final class IcalController
{
    // GET /ical/exposition/{slug}
    public function exhibition(array $params = []): void
    {
        $expo = \App\Expositions\ExhibitionModel::bySlug($params['slug'] ?? '');
        if ($expo === null) {
            Response::notFound();
        }
        $events = \App\Expositions\ExhibitionModel::events((int) $expo['id'], true);
        $lines = [];
        $lines[] = self::vevent(
            $expo['uid'] ?? ('expo-' . $expo['id'] . '@expositions.top'),
            (string) $expo['starts_at'],
            (string) $expo['ends_at'],
            $expo['title'],
            $expo['summary'],
            trim(($expo['place_name'] ?? '') . ', ' . ($expo['commune_name'] ?? ''), ', ')
        );
        foreach ($events as $ev) {
            $lines[] = self::vevent(
                'evt-' . $ev['id'] . '@expositions.top',
                substr((string) $ev['starts_at'], 0, 10),
                null,
                $ev['title'] . ' — ' . $expo['title'],
                $ev['description'] ?? '',
                $ev['place_name'] ?? ''
            );
        }
        self::emit('expositions-' . $expo['slug'] . '.ics', implode("\r\n", $lines));
    }

    // GET /ical/agenda?token=...  (flux personnel, abonnable)
    public function agenda(array $params = []): void
    {
        $token = (string) \Core\Request::query('token', '');
        $user = $token !== '' ? \App\Auth\UserModel::findByRememberSelector($token) : null;
        // Le token d'agenda est stocké côté users (colonne dédiée) — MVP : on accepte le remember selector dérivé
        if ($user === null) {
            // fallback : token = sha256(secret.user_id) recalculé
            $userId = (int) \Core\Request::query('u', 0);
            $sig = hash('sha256', 'expo-agenda|' . $userId . '|' . \Core\Env::get('APP_KEY', 'dev-secret'));
            if (!\Core\Auth::check() || $sig !== $token || \Core\Auth::id() !== $userId) {
                Response::forbidden('Flux invalide.');
            }
        }
        $uid = (int) ($user['id'] ?? \Core\Auth::id());
        $rows = AgendaModel::forUser($uid);
        $lines = [];
        foreach ($rows as $expo) {
            $lines[] = self::vevent(
                'expo-' . $expo['id'] . '@expositions.top',
                (string) $expo['starts_at'],
                $expo['ends_at'] !== null ? date('Y-m-d', strtotime($expo['ends_at'] . ' +1 day')) : null,
                $expo['title'],
                $expo['summary'],
                trim(($expo['place_name'] ?? '') . ', ' . ($expo['commune_name'] ?? ''), ', ')
            );
        }
        self::emit('mon-agenda-expositions.ics', implode("\r\n", $lines));
    }

    /** Lien d'abonnement iCal du membre courant (affiché sur le profil). */
    public static function agendaUrl(int $userId): string
    {
        $sig = hash('sha256', 'expo-agenda|' . $userId . '|' . \Core\Env::get('APP_KEY', 'dev-secret'));
        return rtrim(\Core\Env::get('APP_URL', ''), '/') . '?r=/ical/agenda&u=' . $userId . '&token=' . $sig;
    }

    // ------------------------------------------------------------ helpers

    private static function vevent(string $uid, ?string $dateStart, ?string $dateEnd, string $title, string $desc, string $location): string
    {
        $dtStart = $dateStart !== '' && $dateStart !== null ? gmdate('Ymd\THis\Z', strtotime($dateStart . ' 09:00:00')) : '';
        $dtEnd = $dateEnd !== null && $dateEnd !== ''
            ? gmdate('Ymd\THis\Z', strtotime($dateEnd . ' +1 day 18:00:00'))
            : ($dtStart !== '' ? gmdate('Ymd\THis\Z', strtotime($dateStart . ' 09:00:00') + 7200) : '');
        $esc = fn(string $s) => str_replace(["\\", ";", ",", "\n"], ["\\\\", "\\;", "\\,", "\\n"], $s);
        $out = "BEGIN:VEVENT\r\nUID:$uid\r\n";
        if ($dtStart !== '') {
            $out .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nDTSTART:$dtStart\r\n";
        }
        if ($dtEnd !== '') {
            $out .= "DTEND:$dtEnd\r\n";
        }
        $out .= "SUMMARY:" . $esc(mb_substr($title, 0, 180)) . "\r\n";
        if ($desc !== '') {
            $out .= "DESCRIPTION:" . $esc(mb_substr(strip_tags($desc), 0, 800)) . "\r\n";
        }
        if ($location !== '') {
            $out .= "LOCATION:" . $esc(mb_substr($location, 0, 180)) . "\r\n";
        }
        $out .= "URL:" . rtrim(\Core\Env::get('APP_URL', ''), '/') . "\r\nEND:VEVENT";
        return $out;
    }

    private static function emit(string $filename, string $body): void
    {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//expositions.top//FR\r\nCALSCALE:GREGORIAN\r\n"
            . $body . "\r\nEND:VCALENDAR\r\n";
        exit;
    }
}
