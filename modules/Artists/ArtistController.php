<?php

namespace App\Artists;

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use App\Moderation\ModLog;

final class ArtistController
{
    public function index(array $params = []): void
    {
        $filters = [
            'q' => trim((string) Request::query('q', '')),
            'commune' => (string) Request::query('commune', ''),
        ];
        View::render('artists/list', [
            'title' => 'Annuaire des artistes',
            'rows' => ArtistModel::directory($filters),
            'filters' => $filters,
            'communes' => \App\Models\Commune::allOrdered(),
        ]);
    }

    public function show(array $params = []): void
    {
        $row = ArtistModel::bySlug($params['slug'] ?? '');
        $ownPending = $row !== null && $row['status'] === 'pending'
            && \Core\Auth::check() && ((int) ($row['user_id'] ?? 0) === \Core\Auth::id() || \Core\Auth::isModerator());
        if ($row === null || (!in_array($row['status'], ['published', 'hidden'], true) && !$ownPending)) {
            Response::notFound();
        }
        View::render('artists/show', [
            'title' => $row['name'],
            'artist' => $row,
            'disciplineNames' => ArtistModel::disciplineNames((int) $row['id']),
            'works' => ArtistModel::works((int) $row['id']),
            'expos' => [
                'ongoing' => $this->exposFor((int) $row['id'], 'ongoing'),
                'upcoming' => $this->exposFor((int) $row['id'], 'upcoming'),
                'archived' => $this->exposFor((int) $row['id'], 'archived'),
            ],
            'isFavorite' => Auth::check() && \App\Community\FavoriteModel::has(Auth::id(), 'artist', (int) $row['id']),
        ]);
    }

    private function exposFor(int $artistId, string $scope): array
    {
        $cond = match ($scope) {
            'ongoing' => 'e.status = "published" AND (e.is_permanent = 1 OR (e.starts_at <= CURDATE() AND (e.ends_at IS NULL OR e.ends_at >= CURDATE())))',
            'upcoming' => 'e.status = "published" AND e.starts_at > CURDATE()',
            'archived' => 'e.status = "archived"',
        };
        return Database::run(
            "SELECT e.*, c.nom AS commune_name, cat.color AS category_color, cat.name AS category_name
             FROM exhibitions e
             JOIN exhibition_artists ea ON ea.exhibition_id = e.id
             LEFT JOIN communes c ON c.id = e.commune_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE ea.artist_id = ? AND $cond
             ORDER BY e.starts_at " . ($scope === 'archived' ? 'DESC' : 'ASC') . '
             LIMIT 50',
            [$artistId]
        )->fetchAll();
    }

    // ------------------------------------------------------------ gestion

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
        $this->savePhoto($id, Request::file('photo'));
        ModLog::log('create', 'artist', $id, $data['name']);
        $finalStatus = \Core\Auth::isTrusted() ? 'published' : 'pending';
        Session::flash('success', 'Page artiste enregistrée' . ($finalStatus === 'published' ? '' : ' — en attente de modération') . '.');
        Response::redirect('/artistes/' . $data['slug']);
    }

    public function edit(array $params = []): void
    {
        $row = ArtistModel::find($params['id'] ?? 0);
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
        $row = ArtistModel::find($params['id'] ?? 0);
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
        $this->savePhoto((int) $row['id'], Request::file('photo'));
        ModLog::log('update', 'artist', (int) $row['id'], $data['name']);
        Session::flash('success', 'Page artiste mise à jour.');
        Response::redirect('/artistes/' . $data['slug']);
    }

    /** Revendication d'une page artiste (membre → lier son compte). */
    public function claim(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        $row = ArtistModel::find($params['id'] ?? 0);
        if ($row === null) {
            Response::notFound();
        }
        if ($row['user_id'] !== null) {
            Session::flash('error', 'Cette page est déjà rattachée à un compte.');
            Response::redirect('/artistes/' . $row['slug']);
        }
        Database::run(
            'INSERT INTO claims (kind, entity_id, user_id, email, justification) VALUES ("artist", ?, ?, ?, ?)',
            [$row['id'], Auth::id(), Auth::user()['email'], trim((string) Request::input('justification'))]
        );
        Session::flash('success', 'Demande de revendication envoyée. Un modérateur vous répondra.');
        Response::redirect('/artistes/' . $row['slug']);
    }

    // ------------------------------------------------------------ helpers

    private function renderForm(?array $row, array $data, array $errors): void
    {
        View::render('artists/form', [
            'title' => $row ? 'Modifier — ' . $row['name'] : 'Créer ma page artiste',
            'row' => $row,
            'form' => $data ?: $row ?: [],
            'errors' => $errors,
            'communes' => \App\Models\Commune::allOrdered(),
            'disciplines' => ArtistModel::disciplinesAll(),
            'selectedDisciplines' => $row ? ArtistModel::disciplinesOf((int) $row['id']) : [],
        ]);
    }

    private function validate(): array
    {
        $errors = [];
        $name = trim((string) Request::input('name'));
        if ($name === '') {
            $errors['name'] = 'Le nom (ou nom d\'artiste) est obligatoire.';
        }
        $data = [
            'name' => $name,
            '_discipline_ids' => array_values(array_filter(array_map('intval', (array) Request::input('discipline_ids', [])))),
            'commune_id' => (int) Request::input('commune_id') ?: null,
            'bio' => trim((string) Request::input('bio')),
            'statement' => trim((string) Request::input('statement')),
            'website' => trim((string) Request::input('website')),
            'workshop_open' => (bool) Request::input('workshop_open') ? 1 : 0,
            'workshop_info' => trim((string) Request::input('workshop_info')),
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
            'commune_id' => $data['commune_id'],
            'bio' => $data['bio'] ?: null,
            'statement' => $data['statement'] ?: null,
            'website' => $data['website'] ?: null,
            'workshop_open' => $data['workshop_open'],
            'workshop_info' => $data['workshop_info'] ?: null,
        ];
        $status = Auth::isTrusted() ? 'published' : 'pending';
        if ($id === null) {
            $row['status'] = $status;
            $row['created_by'] = Auth::id();
            $row['user_id'] = Auth::id();
            $id = (int) ArtistModel::create($row);
        } else {
            $current = ArtistModel::find($id);
            $row['status'] = ($current['status'] === 'published' && !Auth::isTrusted()) ? 'pending' : $status;
            ArtistModel::update($id, $row);
        }
        // disciplines (tags administrés)
        Database::run('DELETE FROM artist_disciplines WHERE artist_id = ?', [$id]);
        foreach ($data['_discipline_ids'] as $did) {
            Database::run('INSERT IGNORE INTO artist_disciplines (artist_id, discipline_id) VALUES (?, ?)', [$id, (int) $did]);
        }
        return $id;
    }

    private function savePhoto(int $artistId, ?array $file): void
    {
        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $dir = __DIR__ . '/../uploads/artists';
        $name = \Core\Image::upload($file, $dir, 3, false);
        $old = \Core\Database::run('SELECT image_path FROM artists WHERE id = ?', [$artistId])->fetchColumn();
        if ($old && is_file($dir . '/' . $old)) {
            @unlink($dir . '/' . $old);
        }
        \Core\Database::run('UPDATE artists SET image_path = ? WHERE id = ?', [$name, $artistId]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = slugify($name);
        $slug = $base;
        $i = 1;
        while ((int) Database::run('SELECT COUNT(*) FROM artists WHERE slug = ?', [$slug])->fetchColumn() > 0) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    private function assertCanEdit(array $row): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        if (Auth::isModerator() || (int) ($row['user_id'] ?? 0) === Auth::id()) {
            return;
        }
        Response::forbidden('Seul l\'artiste (compte lié) ou un modérateur peut modifier cette page.');
    }
}
