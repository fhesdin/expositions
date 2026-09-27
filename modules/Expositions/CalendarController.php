<?php

namespace App\Expositions;

use Core\Request;
use Core\View;

/** Vue calendrier mensuelle (périodes + vernissages). */
final class CalendarController
{
    public function month(array $params = []): void
    {
        $monthParam = (string) Request::query('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $monthParam = date('Y-m');
        }
        [$y, $m] = array_map('intval', explode('-', $monthParam));
        $monthStart = \DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $y, $m));
        if ($monthStart === false) {
            $monthStart = \DateTime::createFromFormat('Y-m-d', date('Y-m-01'));
        }

        [$exhibitions, $events] = ExhibitionModel::calendarMonth((int) $monthStart->format('Y'), (int) $monthStart->format('n'));

        // items par jour : barres de période (1 entrée par jour couvert, tronqué au mois)
        $calItems = [];
        foreach ($exhibitions as $e) {
            $start = max(new \DateTimeImmutable((string) $e['starts_at']), new \DateTimeImmutable($monthStart->format('Y-m-d')));
            $end = $e['ends_at'] !== null
                ? min(new \DateTimeImmutable((string) $e['ends_at']), new \DateTimeImmutable($monthStart->format('Y-m-t')))
                : new \DateTimeImmutable($monthStart->format('Y-m-t'));
            for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
                $calItems[] = ['date_key' => $d->format('Y-m-d'), 'kind' => 'expo', 'slug' => $e['slug'],
                    'label' => mb_substr($e['title'], 0, 18), 'color' => $e['category_color'] ?? '#b4552d'];
            }
        }
        foreach ($events as $ev) {
            $calItems[] = ['date_key' => substr((string) $ev['starts_at'], 0, 10), 'kind' => 'event',
                'slug' => $ev['exhibition_slug'], 'label' => mb_substr($ev['title'], 0, 18), 'color' => '#1d2b3a'];
        }

        $legend = \App\Models\Category::allActive();

        View::render('expositions/calendar', [
            'title' => 'Calendrier des expositions',
            'monthStart' => $monthStart,
            'calItems' => $calItems,
            'legend' => $legend,
        ]);
    }
}
