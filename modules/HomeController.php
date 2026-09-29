<?php

namespace App;

use Core\Database;
use Core\Pagination;
use Core\Request;
use Core\View;
use App\Expositions\ExhibitionModel;

final class HomeController
{
    public function index(array $params = []): void
    {
        View::render('home/index', [
            'title' => 'Accueil',
            'featured' => ExhibitionModel::featured(6),
            'weekend' => ExhibitionModel::weekend(6),
            'vernissages' => ExhibitionModel::vernissages(6),
            'lastChance' => ExhibitionModel::lastChance(10, 6),
            'newest' => ExhibitionModel::newest(6),
            'counters' => [
                'ongoing' => ExhibitionModel::ongoingCount(),
                'places' => (int) Database::run('SELECT COUNT(*) FROM places WHERE status = "published"')->fetchColumn(),
                'artists' => (int) Database::run('SELECT COUNT(*) FROM artists WHERE status = "published"')->fetchColumn(),
                'communes' => (int) Database::run('SELECT COUNT(DISTINCT commune_id) FROM exhibitions WHERE status = "published" AND commune_id IS NOT NULL')->fetchColumn(),
                'structures' => (int) Database::run('SELECT COUNT(*) FROM structures WHERE status = "published"')->fetchColumn(),
            ],
            'categories' => \App\Models\Category::allActive(),
            'disciplines' => Database::run(
                'SELECT d.id, d.name, d.slug,
                        (SELECT COUNT(*) FROM artist_disciplines ad JOIN artists a ON a.id = ad.artist_id WHERE ad.discipline_id = d.id AND a.status = "published") AS n
                 FROM disciplines d ORDER BY name'
            )->fetchAll(),
        ]);
    }

    /** Recherche globale + autocomplétion JSON (expositions, artistes, lieux, communes). */
    public function searchSuggest(array $params = []): void
    {
        $q = trim((string) Request::query('q', ''));
        if (mb_strlen($q) < 2) {
            \Core\Response::json(['results' => []]);
        }
        $like = '%' . $q . '%';
        $out = [];

        foreach (Database::run(
            'SELECT id, title, slug FROM exhibitions WHERE status = "published" AND title LIKE ? LIMIT 6',
            [$like]
        )->fetchAll() as $r) {
            $out[] = ['type' => 'Exposition', 'label' => $r['title'], 'url' => '/expositions/' . $r['slug']];
        }
        foreach (Database::run(
            'SELECT id, name, slug FROM places WHERE status = "published" AND name LIKE ? LIMIT 4',
            [$like]
        )->fetchAll() as $r) {
            $out[] = ['type' => 'Lieu', 'label' => $r['name'], 'url' => '/lieux/' . $r['slug']];
        }
        foreach (Database::run(
            'SELECT id, name, slug FROM artists WHERE status = "published" AND name LIKE ? LIMIT 4',
            [$like]
        )->fetchAll() as $r) {
            $out[] = ['type' => 'Artiste', 'label' => $r['name'], 'url' => '/artistes/' . $r['slug']];
        }
        foreach (Database::run(
            'SELECT id, nom FROM communes WHERE nom LIKE ? LIMIT 3',
            [$like]
        )->fetchAll() as $r) {
            $out[] = ['type' => 'Commune', 'label' => $r['nom'], 'url' => '/expositions?commune=' . $r['id']];
        }
        \Core\Response::json(['results' => $out]);
    }

    /** API interne de la carte : marqueurs des lieux avec expositions en cours. */
    public function mapData(array $params = []): void
    {
        $places = \App\Places\PlaceModel::forMap();
        \Core\Response::json(['places' => array_map(fn($p) => [
            'id' => (int) $p['id'],
            'name' => $p['name'],
            'slug' => $p['slug'],
            'lat' => (float) $p['latitude'],
            'lng' => (float) $p['longitude'],
            'type' => $p['type_name'],
            'commune' => $p['commune_name'],
            'ongoing' => (int) $p['ongoing_count'],
        ], $places)]);
    }
}
