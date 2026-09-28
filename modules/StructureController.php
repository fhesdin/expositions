<?php

namespace App;

use App\Models\StructureModel;
use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class StructureController
{
    // ------------------------------------------------------------ public

    public function index(array $params = []): void
    {
        $type = trim((string) Request::query('type', ''));
        $q = trim((string) Request::query('q', ''));
        View::render('structures/index', [
            'title' => 'Structures',
            'rows' => StructureModel::listPublished($type !== '' ? $type : null, $q),
            'types' => StructureModel::TYPES,
            'currentType' => $type,
            'q' => $q,
        ]);
    }

    public function show(array $params = []): void
    {
        $row = StructureModel::bySlug((string) ($params['slug'] ?? ''));
        if ($row === null || ($row['status'] !== 'published' && !Auth::minRank(4) && Auth::id() !== (int) $row['created_by'])) {
            Response::notFound('Structure introuvable.');
        }
        $places = Database::run(
            'SELECT id, slug, name, type_id, (SELECT name FROM place_types t WHERE t.id = p.type_id) AS type_name
             FROM places p WHERE p.structure_id = ? AND p.status = "published" ORDER BY p.name',
            [$row['id']]
        )->fetchAll();
        $expos = Database::run(
            'SELECT id, slug, title, starts_at, ends_at, is_permanent,
                    (starts_at <= NOW() AND (is_permanent = 1 OR ends_at IS NULL OR ends_at >= NOW())) AS ongoing
             FROM exhibitions WHERE structure_id = ? AND status = "published"
             ORDER BY ongoing DESC, starts_at ASC LIMIT 50',
            [$row['id']]
        )->fetchAll();
        View::render('structures/show', [
            'title' => $row['nom'],
            's' => $row,
            'places' => $places,
            'expos' => $expos,
            'myRole' => Auth::id() ? StructureModel::memberRole((int) $row['id'], Auth::id()) : null,
        ]);
    }

    // ------------------------------------------------------------ gestion

    public function create(array $params = []): void
    {
        if (!Auth::minRank(3)) {
            Response::forbidden('Réservé aux contributeurs.');
        }
        $this->renderForm(null, []);
    }

    public function edit(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $row = StructureModel::find($id);
        if ($row === null || !$this->canManage($row)) {
            Response::notFound('Structure introuvable.');
        }
        $this->renderForm($row, []);
    }

    private function renderForm(?array $row, array $errors): void
    {
        View::render('structures/form', [
            'title' => $row ? 'Modifier la structure' : 'Créer une structure',
            'row' => $row,
            'isEdit' => $row !== null,
            'errors' => $errors,
            'types' => StructureModel::TYPES,
            'communes' => Database::run('SELECT id, nom, code_postal FROM communes ORDER BY nom')->fetchAll(),
        ]);
    }

    public function store(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        if (!Auth::minRank(3)) {
            Response::forbidden('Réservé aux contributeurs.');
        }
        [$data, $errors] = $this->validate(null);
        if ($errors) {
            $this->renderForm(null, $errors);
            return;
        }
        $data['slug'] = StructureModel::uniqueSlug($data['nom']);
        $data['status'] = $this->publicationStatus();
        $id = StructureModel::create($data, Auth::id());
        $this->saveLogo($id, Request::file('logo'));
        Session::flash('success', 'Structure enregistrée. Elle est ' . ($data['status'] === 'published' ? 'en ligne' : 'en attente de modération') . '.');
        Response::redirect('/structures/' . $data['slug']);
    }

    public function update(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $row = StructureModel::find($id);
        if ($row === null || !$this->canManage($row)) {
            Response::notFound('Structure introuvable.');
        }
        [$data, $errors] = $this->validate($row);
        if ($errors) {
            $this->renderForm($row, $errors);
            return;
        }
        StructureModel::update($id, $data);
        $this->saveLogo($id, Request::file('logo'));
        Session::flash('success', 'Structure mise à jour.');
        Response::redirect('/structures/' . $row['slug']);
    }

    private function saveLogo(int $structureId, ?array $file): void
    {
        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $dir = __DIR__ . '/../uploads/structures';
        $name = \Core\Image::upload($file, $dir, 3, false);
        $old = \Core\Database::run('SELECT logo_path FROM structures WHERE id = ?', [$structureId])->fetchColumn();
        if ($old && is_file($dir . '/' . $old)) {
            @unlink($dir . '/' . $old);
        }
        \Core\Database::run('UPDATE structures SET logo_path = ? WHERE id = ?', [$name, $structureId]);
    }

    // ------------------------------------------------------------ membres

    public function members(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $row = StructureModel::find($id);
        if ($row === null || !$this->canManage($row)) {
            Response::notFound('Structure introuvable.');
        }
        View::render('structures/members', [
            'title' => 'Membres — ' . $row['nom'],
            's' => $row,
            'members' => StructureModel::members($id),
        ]);
    }

    /** Utilisateur : demande d'adhésion / acceptation d'une invitation. */
    public function join(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) Request::input('structure_id');
        $row = StructureModel::find($id);
        if ($row === null || $row['status'] !== 'published') {
            Response::notFound('Structure introuvable.');
        }
        $existing = Database::run(
            'SELECT status, requested_by FROM structure_members WHERE structure_id = ? AND user_id = ?',
            [$id, Auth::id()]
        )->fetch();
        if ($existing && $existing['status'] === 'active') {
            Session::flash('info', 'Vous êtes déjà membre de cette structure.');
        } elseif ($existing && $existing['requested_by'] === 'structure' && $existing['status'] === 'pending') {
            // invitation en attente → l'utilisateur confirme
            StructureModel::setMember($id, Auth::id(), $existing['role'] ?: 'member', 'active', 'structure');
            Session::flash('success', 'Invitation acceptée : vous êtes membre de « ' . $row['nom'] . ' ».');
        } else {
            StructureModel::setMember($id, Auth::id(), 'member', 'pending', 'user');
            Session::flash('success', 'Demande envoyée. Un responsable de la structure va la confirmer.');
        }
        Response::redirect('/structures/' . $row['slug']);
    }

    /** Gestionnaire : invite un utilisateur par e-mail/username. */
    public function invite(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $row = StructureModel::find($id);
        if ($row === null || !$this->canManage($row)) {
            Response::notFound('Structure introuvable.');
        }
        $who = trim((string) Request::input('who'));
        $user = null;
        if ($who !== '') {
            $user = Database::run('SELECT id FROM users WHERE email = ? OR username = ?', [$who, $who])->fetch();
        }
        if ($user) {
            StructureModel::setMember($id, (int) $user['id'], 'member', 'pending', 'structure');
            Session::flash('success', 'Invitation envoyée. L\'utilisateur la confirmera depuis la fiche de la structure.');
        } else {
            Session::flash('error', 'Aucun utilisateur trouvé pour « ' . $who . ' ».');
        }
        Response::redirect('/structures/' . $id . '/members');
    }

    /** Gestionnaire : valide / refuse une demande, change un rôle. */
    public function memberAction(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $uid = (int) ($params['uid'] ?? 0);
        $row = StructureModel::find($id);
        if ($row === null || !$this->canManage($row)) {
            Response::notFound('Structure introuvable.');
        }
        $action = (string) Request::input('action', '');
        switch ($action) {
            case 'approve':
                $m = Database::run('SELECT role, requested_by FROM structure_members WHERE structure_id = ? AND user_id = ?', [$id, $uid])->fetch();
                StructureModel::setMember($id, $uid, $m['role'] ?: 'member', 'active', $m['requested_by'] ?: 'user');
                Session::flash('success', 'Membre confirmé.');
                break;
            case 'refuse':
                Database::run('UPDATE structure_members SET status = "refused" WHERE structure_id = ? AND user_id = ?', [$id, $uid]);
                Session::flash('info', 'Demande refusée.');
                break;
            case 'role':
                $role = (string) Request::input('role', 'member');
                if (in_array($role, ['member', 'contributor', 'admin'], true)) {
                    Database::run('UPDATE structure_members SET role = ? WHERE structure_id = ? AND user_id = ?', [$role, $id, $uid]);
                    Session::flash('success', 'Rôle mis à jour.');
                }
                break;
            case 'remove':
                StructureModel::removeMember($id, $uid);
                Session::flash('info', 'Membre retiré.');
                break;
        }
        Response::redirect('/structures/' . $id . '/members');
    }

    /** L'utilisateur quitte la structure. */
    public function leave(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) Request::input('structure_id');
        $admins = (int) Database::run(
            'SELECT COUNT(*) FROM structure_members WHERE structure_id = ? AND role = "admin" AND status = "active" AND user_id != ?',
            [$id, Auth::id()]
        )->fetchColumn();
        if ($admins === 0) {
            Session::flash('error', 'Vous êtes le dernier responsable : nommez un autre admin avant de quitter.');
        } else {
            StructureModel::removeMember($id, Auth::id());
            Session::flash('info', 'Vous avez quitté la structure.');
        }
        Response::redirect('/mon-espace');
    }

    // ------------------------------------------------------------ helpers

    private function validate(?array $row): array
    {
        $errors = [];
        $nom = trim((string) Request::input('nom'));
        if ($nom === '' || mb_strlen($nom) > 180) {
            $errors['nom'] = 'Nom requis (180 caractères max).';
        }
        $type = (string) Request::input('type', 'association');
        if (!isset(StructureModel::TYPES[$type])) {
            $errors['type'] = 'Type invalide.';
        }
        $commune = (int) Request::input('commune_id');
        if ($commune !== 0 && !Database::run('SELECT id FROM communes WHERE id = ?', [$commune])->fetch()) {
            $commune = 0;
        }
        $data = [
            'nom' => $nom,
            'type' => $type,
            'description' => trim((string) Request::input('description')),
            'website' => trim((string) Request::input('website')),
            'email' => filter_var(trim((string) Request::input('email')), FILTER_VALIDATE_EMAIL) ?: '',
            'phone' => trim((string) Request::input('phone')),
            'commune_id' => $commune ?: null,
        ];
        if (mb_strlen($data['description']) > 2000) {
            $errors['description'] = 'Description trop longue (2000 max).';
        }
        return [$data, $errors];
    }

    private function publicationStatus(): string
    {
        return Auth::minRank(4) ? 'published' : 'pending';
    }

    private function canManage(array $row): bool
    {
        if (Auth::minRank(5)) {
            return true;
        }
        $role = Auth::id() ? StructureModel::memberRole((int) $row['id'], Auth::id()) : null;
        return $role === 'admin' || (int) $row['created_by'] === Auth::id();
    }
}
