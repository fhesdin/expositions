<?php

namespace App\Places;

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use App\Moderation\ModLog;

final class PlaceController
{
    public function index(array $params = []): void
    {
        $filters = [
            'q' => trim((string) Request::query('q', '')),
            'type' => (string) Request::query('type', ''),
            'commune' => (string) Request::query('commune', ''),
        ];
        $rows = PlaceModel::withOngoingCounts(PlaceModel::directory($filters));
        View::render('places/list', [
            'title' => 'Annuaire des lieux',
            'rows' => $rows,
            'filters' => $filters,
            'types' => PlaceModel::types(),
            'communes' => \App\Models\Commune::allOrdered(),
            'myStructures' => \App\Models\StructureModel::ofUser(Auth::id()),
            'canAllStructures' => Auth::isModerator(),
        ]);
    }

    public function show(array $params = []): void
    {
        $row = PlaceModel::bySlug($params['slug'] ?? '');
        if ($row === null || !in_array($row['status'], ['published', 'hidden'], true)) {
            Response::notFound();
        }
        $ongoing = Database::run(
            'SELECT e.*, cat.color AS category_color, cat.name AS category_name, c.nom AS commune_name
             FROM exhibitions e
             LEFT JOIN categories cat ON cat.id = e.category_id
             LEFT JOIN communes c ON c.id = e.commune_id
             WHERE e.place_id = ? AND e.status = "published"
             AND (e.is_permanent = 1 OR (e.starts_at <= CURDATE() AND (e.ends_at IS NULL OR e.ends_at >= CURDATE())))
             ORDER BY e.starts_at',
            [$row['id']]
        )->fetchAll();
        $upcoming = Database::run(
            'SELECT e.*, cat.color AS category_color, cat.name AS category_name, c.nom AS commune_name
             FROM exhibitions e
             LEFT JOIN categories cat ON cat.id = e.category_id
             LEFT JOIN communes c ON c.id = e.commune_id
             WHERE e.place_id = ? AND e.status = "published" AND e.starts_at > CURDATE()
             ORDER BY e.starts_at LIMIT 20',
            [$row['id']]
        )->fetchAll();
        $archived = Database::run(
            'SELECT e.*, c.nom AS commune_name FROM exhibitions e
             LEFT JOIN communes c ON c.id = e.commune_id
             WHERE e.place_id = ? AND e.status = "archived" ORDER BY e.ends_at DESC LIMIT 30',
            [$row['id']]
        )->fetchAll();
        View::render('places/show', [
            'title' => $row['name'],
            'place' => $row,
            'structure' => !empty($row['structure_id']) ? \App\Models\StructureModel::find((int) $row['structure_id']) : null,
            'managers' => PlaceModel::managers((int) $row['id']),
            'ongoing' => $ongoing,
            'upcoming' => $upcoming,
            'archived' => $archived,
            'isFavorite' => Auth::check() && \App\Community\FavoriteModel::has(Auth::id(), 'place', (int) $row['id']),
            'leaflet' => !empty($row['latitude']) && !empty($row['longitude']),
            'mapPoints' => (!empty($row['latitude']) && !empty($row['longitude'])) ? [[
                'lat' => (float) $row['latitude'],
                'lng' => (float) $row['longitude'],
                'title' => $row['name'],
            ]] : [],
        ]);
    }

    // ------------------------------------------------------------ gestion (contributeur +)

    public function create(array $params = []): void
    {
        $this->renderForm(null, [], []);
    }

    public function store(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        [$data, $errors] = $this->validate();
        if ($errors !== []) {
            $this->renderForm(null, $data, $errors);
            return;
        }
        $id = $this->persist(null, $data);
        ModLog::log('create', 'place', $id, $data['name']);
        $finalStatus = \Core\Auth::isTrusted() ? 'published' : 'pending';
        Session::flash('success', 'Lieu enregistré' . ($finalStatus === 'published' ? '' : ' — en attente de modération') . '.');
        Response::redirect('/lieux/' . $data['slug']);
    }

    public function edit(array $params = []): void
    {
        $row = PlaceModel::find($params['id'] ?? 0);
        if ($row === null) {
            Response::notFound();
        }
        $this->assertCanEdit($row);
        $this->renderForm($row, [], []);
    }

    public function update(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $row = PlaceModel::find($params['id'] ?? 0);
        if ($row === null) {
            Response::notFound();
        }
        $this->assertCanEdit($row);
        [$data, $errors] = $this->validate();
        if ($errors !== []) {
            $this->renderForm($row, $data, $errors);
            return;
        }
        $this->persist((int) $row['id'], $data);
        ModLog::log('update', 'place', (int) $row['id'], $data['name']);
        Session::flash('success', 'Lieu mis à jour.');
        Response::redirect('/lieux/' . $data['slug']);
    }

    // ------------------------------------------------------------ helpers

    private function renderForm(?array $row, array $data, array $errors): void
    {
        View::render('places/form', [
            'title' => $row ? 'Modifier — ' . $row['name'] : 'Créer un lieu',
            'row' => $row,
            'form' => $data ?: $row ?: [],
            'errors' => $errors,
            'types' => PlaceModel::types(),
            'communes' => \App\Models\Commune::allOrdered(),
            'myStructures' => \App\Models\StructureModel::ofUser(Auth::id()),
            'canAllStructures' => Auth::isModerator(),
        ]);
    }

    private function validate(): array
    {
        $errors = [];
        $name = trim((string) Request::input('name'));
        if ($name === '') {
            $errors['name'] = 'Le nom du lieu est obligatoire.';
        }
        $data = [
            'name' => $name,
            'type_id' => (int) Request::input('type_id') ?: null,
            'commune_id' => (int) Request::input('commune_id') ?: null,
            'address' => trim((string) Request::input('address')),
            'latitude' => (float) str_replace(',', '.', (string) Request::input('latitude')) ?: null,
            'longitude' => (float) str_replace(',', '.', (string) Request::input('longitude')) ?: null,
            'description' => trim((string) Request::input('description')),
            'opening_hours' => trim((string) Request::input('opening_hours')),
            'access_pmr' => (bool) Request::input('access_pmr') ? 1 : 0,
            'phone' => trim((string) Request::input('phone')),
            'email' => trim((string) Request::input('email')),
            'website' => trim((string) Request::input('website')),
            'structure_id' => (int) Request::input('structure_id') ?: null,
        ];
        $current = trim((string) Request::input('current_slug'));
        $data['slug'] = $current !== '' ? $current : $this->uniqueSlug($name);
        return [$data, $errors];
    }

    private function persist(?int $id, array $data): int
    {
        $row = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'type_id' => $data['type_id'],
            'commune_id' => $data['commune_id'],
            'address' => $data['address'] ?: null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'description' => $data['description'] ?: null,
            'opening_hours' => $data['opening_hours'] ?: null,
            'access_pmr' => $data['access_pmr'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'website' => $data['website'] ?: null,
            'structure_id' => $data['structure_id'] ?? null,
        ];
        $status = $this->publicationStatus();
        if ($id === null) {
            $row['status'] = $status;
            $row['created_by'] = Auth::id();
            $id = (int) PlaceModel::create($row);
            // le créateur devient gestionnaire principal
            if (Auth::check()) {
                Database::run('INSERT INTO place_managers (place_id, user_id, is_primary) VALUES (?, ?, 1)', [$id, Auth::id()]);
            }
        } else {
            $row['status'] = $status === 'published' ? $row['status'] ?? 'published' : $status;
            $current = PlaceModel::find($id);
            $row['status'] = ($current['status'] === 'published' && $status === 'pending') ? 'pending' : $status;
            PlaceModel::update($id, $row);
        }
        return $id;
    }

    private function publicationStatus(): string
    {
        if (Auth::isModerator()) {
            return 'published';
        }
        return Auth::isTrusted() ? 'published' : 'pending';
    }

    private function uniqueSlug(string $name): string
    {
        $base = slugify($name);
        $slug = $base;
        $i = 1;
        while ((int) Database::run('SELECT COUNT(*) FROM places WHERE slug = ?', [$slug])->fetchColumn() > 0) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    private function assertCanEdit(array $row): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        if (Auth::isModerator() || PlaceModel::isManager((int) $row['id'], Auth::id())) {
            return;
        }
        Response::forbidden('Seuls les gestionnaires du lieu peuvent le modifier.');
    }
}
