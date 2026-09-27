<?php

namespace App\Expositions;

use Core\Auth;
use Core\Csrf;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Pagination;
use Core\View;
use App\Auth\UserModel;
use App\Moderation\ModLog;

final class ExhibitionController
{
    // ============================================================ public

    public function index(array $params = []): void
    {
        $filters = $this->filtersFromRequest();
        $perPage = 12;
        $page = max(1, (int) (\Core\Request::query('page', 1)));
        $rows = ExhibitionModel::search($filters, $perPage, $page, $total);
        $pagination = new Pagination($total, $perPage, $page);

        View::render('expositions/list', [
            'title' => 'Les expositions',
            'rows' => $rows,
            'filters' => $filters,
            'pagination' => $pagination,
            'categories' => \App\Models\Category::allActive(),
            'communes' => \App\Models\Commune::allOrdered(),
            'placeTypes' => \App\Places\PlaceModel::types(),
        ]);
    }

    public function show(array $params = []): void
    {
        $row = ExhibitionModel::bySlug($params['slug'] ?? '');
        if ($row === null) {
            Response::notFound();
        }
        $inArchiveScope = $row['status'] === 'archived';
        if (!in_array($row['status'], ExhibitionModel::PUBLIC_STATUSES, true)) {
            // draft/pending/rejected : visible uniquement par contributeur propriétaire, modérateur, admin
            if (!Auth::check() || !(Auth::isModerator() || (int) $row['submitted_by'] === Auth::id())) {
                Response::notFound();
            }
        }
        ExhibitionModel::incrementView((int) $row['id']);

        $artistIds = array_map(fn($a) => (int) ($a['artist_id'] ?? 0), ExhibitionModel::artists((int) $row['id']));

        View::render('expositions/show', [
            'title' => $row['title'],
            'expo' => $row,
            'artists' => ExhibitionModel::artists((int) $row['id']),
            'tags' => ExhibitionModel::tags((int) $row['id']),
            'images' => ExhibitionModel::images((int) $row['id']),
            'docs' => ExhibitionModel::docs((int) $row['id']),
            'events' => ExhibitionModel::events((int) $row['id']),
            'suggestions' => array_merge(
                ExhibitionModel::samePlace((int) ($row['place_id'] ?? 0), (int) $row['id'], 4),
                ExhibitionModel::sameArtists($artistIds, (int) $row['id'], 4)
            ),
            'isFavorite' => Auth::check() && \App\Community\FavoriteModel::has(Auth::id(), 'exhibition', (int) $row['id']),
            'myVisit' => Auth::check() ? \App\Community\VisitModel::get(Auth::id(), (int) $row['id']) : null,
            'visitCounts' => \App\Community\VisitModel::counts((int) $row['id']),
            'openGraph' => [
                'title' => $row['title'],
                'description' => $row['summary'],
                'image' => $row['poster_path'] ? \Core\Env::get('APP_URL', '') . $row['poster_path'] : null,
            ],
        ]);
    }

    // ============================================================ contribution

    public function create(array $params = []): void
    {
        $this->renderForm(null, [], []);
    }

    public function store(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        [$data, $errors] = $this->validateAndCollect();
        if ($errors !== []) {
            $this->renderForm(null, $data, $errors);
            return;
        }
        $id = $this->persist(null, $data);
        $finalStatus = $this->publicationStatus();
        Session::flash('success', 'Exposition enregistrée. Elle est ' . ($finalStatus === 'published' ? 'en ligne' : 'en attente de modération') . '.');
        Response::redirect('/expositions/' . $data['slug']);
    }

    public function edit(array $params = []): void
    {
        $row = ExhibitionModel::find($params['id'] ?? 0);
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
        $row = ExhibitionModel::find($params['id'] ?? 0);
        if ($row === null) {
            Response::notFound();
        }
        $this->assertCanEdit($row);
        [$data, $errors] = $this->validateAndCollect();
        if ($errors !== []) {
            $this->renderForm($row, $data, $errors);
            return;
        }
        // Règle 5.4-4 : modification dates/lieu/titre d'une fiche publiée → repasse en modération si confiance faible
        $backToModeration = $row['status'] === 'published'
            && !Auth::isTrusted()
            && (
                (string) $data['title'] !== (string) $row['title']
                || (string) ($data['starts_at'] ?? '') !== (string) substr((string) $row['starts_at'], 0, 10)
                || (string) ($data['ends_at'] ?? '') !== (string) substr((string) $row['ends_at'], 0, 10)
                || (int) $data['place_id'] !== (int) $row['place_id']
            );
        $id = $this->persist((int) $row['id'], $data, $backToModeration);
        ModLog::log('update', 'exhibition', (int) ($id ?? $row['id']), $backToModeration ? 'retour en modération' : null);
        Session::flash('success', 'Exposition mise à jour.');
        Response::redirect('/expositions/' . $data['slug']);
    }

    public function duplicate(array $params = []): void
    {
        $row = ExhibitionModel::find($params['id'] ?? 0);
        if ($row === null) {
            Response::notFound();
        }
        $this->assertCanEdit($row);
        $newId = (int) ExhibitionModel::create([
            'slug' => $this->uniqueSlug($row['title']),
            'title' => $row['title'],
            'place_id' => $row['place_id'],
            'commune_id' => $row['commune_id'],
            'summary' => $row['summary'],
            'description' => $row['description'],
            'is_permanent' => $row['is_permanent'],
            'price_kind' => $row['price_kind'],
            'price_detail' => $row['price_detail'],
            'category_id' => $row['category_id'],
            'status' => 'draft',
            'submitted_by' => Auth::id(),
        ]);
        foreach (ExhibitionModel::tags($row['id']) as $t) {
            \Core\Database::run('INSERT INTO exhibition_tags (exhibition_id, tag_id) VALUES (?, ?)', [$newId, $t['id']]);
        }
        foreach (ExhibitionModel::artists((int) $row['id']) as $a) {
            \Core\Database::run(
                'INSERT INTO exhibition_artists (exhibition_id, artist_id, free_name) VALUES (?, ?, ?)',
                [$newId, $a['artist_id'], $a['free_name']]
            );
        }
        Session::flash('success', 'Exposition dupliquée en brouillon. Modifiez les dates puis soumettez.');
        Response::redirect('/expositions/' . \Core\Database::run('SELECT slug FROM exhibitions WHERE id = ?', [$newId])->fetchColumn() . '/edit');
    }

    public function delete(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $row = ExhibitionModel::find($params['id'] ?? 0);
        if ($row === null) {
            Response::notFound();
        }
        $this->assertCanEdit($row);
        if (Auth::isModerator() || $row['status'] === 'draft') {
            ExhibitionModel::delete((int) $row['id']);
            ModLog::log('delete', 'exhibition', (int) $row['id'], $row['title']);
            Session::flash('success', 'Exposition supprimée.');
        } else {
            Session::flash('error', 'Seules les fiches en brouillon peuvent être supprimées par un contributeur.');
        }
        Response::redirect('/mon-espace');
    }

    // ============================================================ proposition sans compte

    public function proposeForm(array $params = []): void
    {
        View::render('expositions/propose', [
            'title' => 'Proposer une exposition',
            'form' => ['title' => '', 'summary' => '', 'contact_email' => ''],
            'errors' => [],
            'communes' => \App\Models\Commune::allOrdered(),
            'places' => \App\Places\PlaceModel::directory(),
            'categories' => \App\Models\Category::allActive(),
        ]);
    }

    public function propose(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $errors = [];
        $form = [
            'title' => trim((string) Request::input('title')),
            'place_id' => (int) Request::input('place_id'),
            'commune' => trim((string) Request::input('commune')),
            'd1' => (string) Request::input('d1'),
            'd2' => (string) Request::input('d2'),
            'summary' => trim((string) Request::input('summary')),
            'contact_email' => trim((string) Request::input('contact_email')),
        ];
        if ($form['title'] === '' || $form['summary'] === '') {
            $errors['title'] = 'Titre et résumé sont obligatoires.';
        }
        if (!filter_var($form['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'E-mail de contact obligatoire (nous vous poserons une question pour vérifier).';
        }
        $dup = ExhibitionModel::findDuplicateCandidate($form['place_id'], $form['title'], $form['d1'] ?: null, $form['d2'] ?: null);
        if ($dup !== null) {
            $errors['title'] = 'Une exposition similaire existe déjà pour ce lieu à ces dates (« ' . $dup['title'] . ' »). S\'agit-il de la même ? Précisez-le dans votre message.';
        }
        if ($errors !== []) {
            View::render('expositions/propose', [
                'title' => 'Proposer une exposition',
                'form' => $form, 'errors' => $errors,
                'communes' => \App\Models\Commune::allOrdered(),
                'places' => \App\Places\PlaceModel::directory(),
                'categories' => \App\Models\Category::allActive(),
            ]);
            return;
        }
        \Core\Database::run(
            'INSERT INTO suggestions (kind, title, commune, dates, message, email, place_name)
             VALUES ("missing_expo", ?, ?, ?, ?, ?, (SELECT name FROM places WHERE id = ?))',
            [$form['title'], $form['commune'] ?: null, trim($form['d1'] . ' → ' . $form['d2'], ' →'), $form['summary'], $form['contact_email'], $form['place_id'] ?: null]
        );
        \App\Auth\MailService::notifyModerators(
            'Nouvelle proposition d\'exposition',
            '<p>Proposition de <strong>' . e($form['contact_email']) . '</strong> :</p><p><strong>' . e($form['title']) . '</strong></p><p>' . e($form['summary']) . '</p>'
        );
        Session::flash('success', 'Merci ! Votre proposition a été transmise à la modération. Nous vous répondrons à l\'adresse indiquée.');
        Response::redirect('/');
    }

    // ============================================================ events

    public function eventForm(array $params = []): void
    {
        $expo = ExhibitionModel::find($params['id'] ?? 0);
        if ($expo === null) {
            Response::notFound();
        }
        $this->assertCanEdit($expo);
        View::render('expositions/event_form', [
            'title' => 'Événement — ' . $expo['title'],
            'expo' => $expo,
            'event' => null,
            'errors' => [],
        ]);
    }

    public function eventStore(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $expo = ExhibitionModel::find($params['id'] ?? 0);
        if ($expo === null) {
            Response::notFound();
        }
        $this->assertCanEdit($expo);
        $errors = [];
        $data = [
            'exhibition_id' => (int) $expo['id'],
            'type' => in_array((string) Request::input('type'), ['vernissage','finissage','visite','conference','atelier','rencontre','nocturne','jpo','autre'], true)
                ? (string) Request::input('type') : 'autre',
            'title' => trim((string) Request::input('title')),
            'starts_at' => trim((string) Request::input('starts_at')),
            'duration_min' => (int) Request::input('duration_min') ?: null,
            'description' => trim((string) Request::input('description')),
            'price_kind' => (string) Request::input('price_kind', 'libre'),
            'booking_url' => trim((string) Request::input('booking_url')),
        ];
        if ($data['title'] === '') {
            $errors['title'] = 'Titre obligatoire.';
        }
        if ($data['starts_at'] === '') {
            $errors['starts_at'] = 'Date et heure obligatoires.';
        }
        if ($errors !== []) {
            View::render('expositions/event_form', ['title' => 'Événement', 'expo' => $expo, 'event' => $data, 'errors' => $errors]);
            return;
        }
        \Core\Database::run(
            'INSERT INTO exhibition_events (exhibition_id, type, title, starts_at, duration_min, description, price_kind, booking_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['exhibition_id'], $data['type'], $data['title'],
                date('Y-m-d H:i:s', strtotime($data['starts_at'])),
                $data['duration_min'], $data['description'] ?: null, $data['price_kind'],
                $data['booking_url'] ?: null,
                $this->publicationStatus(),
            ]
        );
        Session::flash('success', 'Événement ajouté.');
        Response::redirect('/expositions/' . $expo['slug']);
    }

    public function eventDelete(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $ev = \Core\Database::run('SELECT ev.*, e.slug AS expo_slug, e.submitted_by FROM exhibition_events ev
             JOIN exhibitions e ON e.id = ev.exhibition_id WHERE ev.id = ?', [$params['id'] ?? 0])->fetch();
        if ($ev === false) {
            Response::notFound();
        }
        if (!(Auth::isModerator() || (int) $ev['submitted_by'] === Auth::id())) {
            Response::forbidden('Action non permise.');
        }
        \Core\Database::run('DELETE FROM exhibition_events WHERE id = ?', [$ev['id']]);
        Session::flash('success', 'Événement supprimé.');
        Response::redirect('/expositions/' . $ev['expo_slug']);
    }

    // ============================================================ helpers

    private function filtersFromRequest(): array
    {
        $q = static fn(string $key, mixed $default = '') => \Core\Request::query($key, $default);
        $f = [
            'q' => trim((string) ($q('q', ''))),
            'category' => (string) $q('category', ''),
            'commune' => (string) $q('commune', ''),
            'tag' => (string) $q('tag', ''),
            'type' => (string) $q('type', ''),
            'free' => (string) $q('free', ''),
            'pmr' => (string) $q('pmr', ''),
            'public' => (string) $q('public', ''),
            'period' => (string) $q('period', ''),
            'd1' => (string) $q('d1', ''),
            'd2' => (string) $q('d2', ''),
            'sort' => (string) $q('sort', 'pertinence'),
            'scope' => in_array((string) $q('scope', 'active'), ['active', 'archives', 'all'], true) ? (string) $q('scope', 'active') : 'active',
        ];
        return array_filter($f, fn($v) => $v !== null && $v !== '');
    }

    private function renderForm(?array $row, array $data, array $errors): void
    {
        View::render('expositions/form', [
            'title' => $row ? 'Modifier — ' . $row['title'] : 'Créer une exposition',
            'row' => $row,
            'form' => $data ?: $row ?: [],
            'errors' => $errors,
            'categories' => \App\Models\Category::allActive(),
            'communes' => \App\Models\Commune::allOrdered(),
            'places' => \App\Places\PlaceModel::directory(),
            'tags' => \App\Models\Tag::all(),
        ]);
    }

    private function validateAndCollect(): array
    {
        $errors = [];
        $title = trim((string) Request::input('title'));
        $summary = trim((string) Request::input('summary'));
        $placeId = (int) Request::input('place_id') ?: null;
        $communeId = (int) Request::input('commune_id') ?: null;
        $d1 = (string) Request::input('starts_at');
        $d2 = (string) Request::input('ends_at');
        $permanent = (bool) Request::input('is_permanent');

        if ($title === '') {
            $errors['title'] = 'Le titre est obligatoire.';
        } elseif (mb_strlen($title) > 120) {
            $errors['title'] = '120 caractères maximum.';
        }
        if ($summary === '') {
            $errors['summary'] = 'Le résumé est obligatoire (utilisé dans les cartes et le partage).';
        } elseif (mb_strlen($summary) > 300) {
            $errors['summary'] = '300 caractères maximum.';
        }
        if (!$permanent && $d1 === '') {
            $errors['starts_at'] = 'Date de début obligatoire (sauf exposition permanente).';
        }
        if (!$permanent && $d2 !== '' && $d1 !== '' && $d2 < $d1) {
            $errors['ends_at'] = 'La date de fin doit suivre la date de début.';
        }
        if ($permanent && $d2 !== '') {
            $d2 = '';
        }
        if ($placeId === null && $communeId === null) {
            $errors['place_id'] = 'Choisissez un lieu référencé (ou une commune pour un lieu à créer).';
        }

        $data = [
            'title' => $title,
            'summary' => $summary,
            'description' => trim((string) Request::input('description')),
            'place_id' => $placeId,
            'commune_id' => $communeId,
            'starts_at' => $d1 !== '' ? $d1 : null,
            'ends_at' => $d2 !== '' ? $d2 : null,
            'is_permanent' => $permanent ? 1 : 0,
            'opening_hours' => trim((string) Request::input('opening_hours')),
            'commissionner' => trim((string) Request::input('commissionner')),
            'price_kind' => in_array((string) Request::input('price_kind'), ['gratuit', 'payant', 'reservation', 'libre'], true) ? (string) Request::input('price_kind') : 'libre',
            'price_detail' => trim((string) Request::input('price_detail')),
            'ticket_url' => trim((string) Request::input('ticket_url')),
            'video_url' => trim((string) Request::input('video_url')),
            'access_pmr' => (bool) Request::input('access_pmr') ? 1 : 0,
            'languages' => trim((string) Request::input('languages')),
            'category_id' => (int) Request::input('category_id') ?: null,
            'label_id' => (int) Request::input('label_id') ?: null,
            'poster_credit' => trim((string) Request::input('poster_credit')),
        ];

        // artistes + tags (listes d'ids / noms libres ; tolère tableau ou chaîne ;-séparée)
        $flatList = static function ($input): array {
            $parts = [];
            foreach ((array) $input as $piece) {
                foreach (explode(';', (string) $piece) as $p) {
                    $p = trim($p);
                    if ($p !== '') {
                        $parts[] = $p;
                    }
                }
            }
            return array_values(array_unique($parts));
        };
        $data['_artist_ids'] = array_values(array_filter(array_map('intval', (array) Request::input('artist_ids', []))));
        $data['_free_artists'] = $flatList(Request::input('free_artists', []));
        $data['_tag_ids'] = array_values(array_filter(array_map('intval', (array) Request::input('tag_ids', []))));
        $data['_new_tags'] = $flatList(Request::input('new_tags', []));
        $data['_public_ids'] = array_values(array_filter(array_map('intval', (array) Request::input('public_ids', []))));

        // slug
        $data['slug'] = $this->uniqueSlug($title);

        return [$data, $errors];
    }

    private function persist(?int $id, array $data, bool $backToModeration = false): int
    {
        $row = [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'place_id' => $data['place_id'],
            'commune_id' => $data['commune_id'] ?? ($data['place_id'] ? \Core\Database::run('SELECT commune_id FROM places WHERE id = ?', [$data['place_id']])->fetchColumn() ?: null : null),
            'summary' => $data['summary'],
            'description' => $data['description'] ?: null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_permanent' => $data['is_permanent'],
            'opening_hours' => $data['opening_hours'] ?: null,
            'commissionner' => $data['commissionner'] ?: null,
            'price_kind' => $data['price_kind'],
            'price_detail' => $data['price_detail'] ?: null,
            'ticket_url' => $data['ticket_url'] ?: null,
            'video_url' => $data['video_url'] ?: null,
            'access_pmr' => $data['access_pmr'],
            'languages' => $data['languages'] ?: null,
            'category_id' => $data['category_id'],
            'label_id' => $data['label_id'],
            'poster_credit' => $data['poster_credit'] ?: null,
            'submitted_by' => Auth::id(),
        ];

        $status = $backToModeration ? 'pending' : $this->publicationStatus();
        if ($id === null) {
            $row['status'] = $status;
            $id = (int) ExhibitionModel::create($row);
        } else {
            $row['status'] = $status;
            $row['moderated_by'] = null;
            $row['reject_reason'] = null;
            ExhibitionModel::update($id, $row);
        }

        // relations
        \Core\Database::run('DELETE FROM exhibition_artists WHERE exhibition_id = ?', [$id]);
        foreach ($data['_artist_ids'] as $aid) {
            \Core\Database::run('INSERT INTO exhibition_artists (exhibition_id, artist_id) VALUES (?, ?)', [$id, (int) $aid]);
        }
        foreach ($data['_free_artists'] as $name) {
            if ($name !== '') {
                \Core\Database::run('INSERT INTO exhibition_artists (exhibition_id, free_name) VALUES (?, ?)', [$id, $name]);
            }
        }
        \Core\Database::run('DELETE FROM exhibition_tags WHERE exhibition_id = ?', [$id]);
        $tagCount = 0;
        foreach ($data['_tag_ids'] as $tid) {
            if ($tagCount >= 10) {
                break;
            }
            \Core\Database::run('INSERT INTO exhibition_tags (exhibition_id, tag_id) VALUES (?, ?)', [$id, (int) $tid]);
            $tagCount++;
        }
        foreach ($data['_new_tags'] as $name) {
            if ($name === '' || $tagCount >= 10) {
                continue;
            }
            $t = \App\Models\Tag::findBySlugOrCreate($name, Auth::isTrusted());
            if ($t !== null) {
                \Core\Database::run('INSERT INTO exhibition_tags (exhibition_id, tag_id) VALUES (?, ?)', [$id, $t['id']]);
                $tagCount++;
            }
        }
        \Core\Database::run('DELETE FROM exhibition_publics WHERE exhibition_id = ?', [$id]);
        foreach ($data['_public_ids'] as $pid) {
            \Core\Database::run('INSERT INTO exhibition_publics (exhibition_id, public_id) VALUES (?, ?)', [$id, (int) $pid]);
        }

        return $id;
    }

    /** Statut à la soumission selon le niveau de confiance. */
    private function publicationStatus(): string
    {
        if (Auth::isModerator()) {
            return 'published';
        }
        return Auth::isTrusted() ? 'published' : 'pending';
    }

    private function uniqueSlug(string $title): string
    {
        $base = slugify($title);
        $slug = $base;
        $i = 1;
        while ((int) \Core\Database::run('SELECT COUNT(*) FROM exhibitions WHERE slug = ?', [$slug])->fetchColumn() > 0) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    private function assertCanEdit(array $row): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        if (Auth::isModerator() || (int) ($row['submitted_by'] ?? 0) === Auth::id()) {
            return;
        }
        Response::forbidden('Vous ne pouvez modifier que vos propres fiches.');
    }
}
