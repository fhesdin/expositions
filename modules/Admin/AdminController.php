<?php

namespace App\Admin;

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Pagination;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use App\Auth\UserModel;
use App\Moderation\ModLog;

final class AdminController
{
    // ------------------------------------------------------------ users

    public function users(array $params = []): void
    {
        $page = max(1, (int) Request::query('page', 1));
        $perPage = 30;
        $total = (int) Database::run('SELECT COUNT(*) FROM users')->fetchColumn();
        $rows = UserModel::paginate($perPage, ($page - 1) * $perPage);
        View::render('admin/users', [
            'title' => 'Utilisateurs',
            'rows' => $rows,
            'pagination' => new Pagination($total, $perPage, $page),
            'roles' => Database::run('SELECT * FROM roles ORDER BY `rank`')->fetchAll(),
        ]);
    }

    public function userCreate(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $email = strtolower(trim((string) Request::input('email')));
        $username = trim((string) Request::input('username'));
        $password = (string) Request::input('password');
        $roleId = (int) Request::input('role_id', 2);

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail invalide.';
        }
        if (mb_strlen($username) < 3) {
            $errors[] = 'Pseudonyme trop court (3 caractères min).';
        }
        if (mb_strlen($password) < 10) {
            $errors[] = 'Mot de passe trop court (10 caractères min).';
        }
        if (UserModel::emailExists($email)) {
            $errors[] = 'E-mail déjà utilisé.';
        }
        if (UserModel::usernameExists($username)) {
            $errors[] = 'Pseudonyme déjà pris.';
        }
        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect('/admin/users');
        }
        $id = UserModel::create([
            'email' => $email,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $roleId,
            'is_active' => 1,
            'email_confirmed_at' => date('Y-m-d H:i:s'),
            'commune_id' => (int) Request::input('commune_id') ?: null,
        ]);
        ModLog::log('user.create', 'user', $id, $username);
        Session::flash('success', 'Utilisateur « ' . $username . ' » créé (compte confirmé, actif).');
        Response::redirect('/admin/users');
    }

    public function userEdit(array $params = []): void
    {
        $row = UserModel::find((int) ($params['id'] ?? 0));
        if ($row === null) {
            Response::notFound();
        }
        View::render('admin/user_form', [
            'title' => 'Utilisateur — ' . $row['username'],
            'row' => $row,
            'roles' => Database::run('SELECT * FROM roles ORDER BY `rank`')->fetchAll(),
            'communes' => \App\Models\Commune::allOrdered(),
        ]);
    }

    public function userUpdate(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $row = UserModel::find($id);
        if ($row === null) {
            Response::notFound();
        }
        $roleId = (int) Request::input('role_id');
        $active = (bool) Request::input('is_active') ? 1 : 0;
        $trusted = (int) Request::input('trusted_level', 1);
        $artistVerified = (bool) Request::input('artist_verified') ? 1 : 0;
        if ($id === Auth::id() && ($active === 0 || $roleId < 5)) {
            Session::flash('error', 'Impossible de se retirer ses propres droits d\'admin.');
            Response::redirect('/admin/users');
        }
        UserModel::update($id, [
            'role_id' => $roleId,
            'is_active' => $active,
            'trusted_level' => $trusted,
            'artist_verified' => $artistVerified,
        ]);
        ModLog::log('user.update', 'user', $id, 'role=' . $roleId . ' active=' . $active . ' trusted=' . $trusted);
        Session::flash('success', 'Utilisateur mis à jour.');
        Response::redirect('/admin/users');
    }

    // ------------------------------------------------------------ taxonomie

    public function taxonomy(array $params = []): void
    {
        View::render('admin/taxonomy', [
            'title' => 'Taxonomie',
            'categories' => Database::run('SELECT * FROM categories ORDER BY name')->fetchAll(),
            'tags' => Database::run('SELECT t.*, (SELECT COUNT(*) FROM exhibition_tags et WHERE et.tag_id = t.id) AS used FROM tags t ORDER BY t.name')->fetchAll(),
            'placeTypes' => Database::run('SELECT * FROM place_types ORDER BY name')->fetchAll(),
            'publics' => Database::run('SELECT * FROM publics ORDER BY id')->fetchAll(),
        ]);
    }

    public function categoryStore(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $name = trim((string) Request::input('name'));
        if ($name !== '') {
            $color = (string) Request::input('color', '#607d8b');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $color = '#607d8b';
            }
            Database::run(
                'INSERT INTO categories (slug, name, color, icon) VALUES (?, ?, ?, ?)',
                [slugify($name), $name, $color, trim((string) Request::input('icon')) ?: null]
            );
            ModLog::log('create', 'category', null, $name);
            Session::flash('success', 'Catégorie ajoutée.');
        }
        Response::redirect('/admin/taxonomy');
    }

    public function categoryUpdate(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $name = trim((string) Request::input('name'));
        if ($name !== '') {
            $color = (string) Request::input('color', '#607d8b');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $color = '#607d8b';
            }
            Database::run('UPDATE categories SET name = ?, color = ?, is_active = ? WHERE id = ?', [
                $name, $color, (bool) Request::input('is_active') ? 1 : 0, $id,
            ]);
            ModLog::log('update', 'category', $id, $name);
            Session::flash('success', 'Catégorie mise à jour.');
        }
        Response::redirect('/admin/taxonomy');
    }

    public function tagMerge(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $fromId = (int) Request::input('from_id');
        $intoId = (int) Request::input('into_id');
        if ($fromId > 0 && $intoId > 0 && $fromId !== $intoId) {
            // reprendre les liaisons en évitant les doublons (PK composée)
            Database::run(
                'INSERT IGNORE INTO exhibition_tags (exhibition_id, tag_id)
                 SELECT exhibition_id, ? FROM exhibition_tags WHERE tag_id = ?',
                [$intoId, $fromId]
            );
            Database::run('DELETE FROM exhibition_tags WHERE tag_id = ?', [$fromId]);
            Database::run('DELETE FROM tags WHERE id = ?', [$fromId]);
            ModLog::log('merge', 'tag', $intoId, "fusion #$fromId → #$intoId");
            Session::flash('success', 'Tags fusionnés.');
        }
        Response::redirect('/admin/taxonomy');
    }

    public function tagApprove(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        Database::run('UPDATE tags SET is_approved = 1 WHERE id = ?', [$id]);
        ModLog::log('approve', 'tag', $id);
        Session::flash('success', 'Tag approuvé.');
        Response::redirect('/moderation');
    }

    public function tagStore(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $name = trim((string) Request::input('name'));
        if ($name !== '' && mb_strlen($name) <= 80) {
            try {
                Database::run(
                    'INSERT INTO tags (slug, name, is_approved) VALUES (?, ?, 1)',
                    [slugify($name), $name]
                );
                ModLog::log('create', 'tag', null, $name);
                Session::flash('success', 'Tag ajouté.');
            } catch (\PDOException) {
                Session::flash('error', 'Ce tag existe déjà.');
            }
        }
        Response::redirect('/admin/taxonomy');
    }

    public function tagUpdate(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $name = trim((string) Request::input('name'));
        if ($id > 0 && $name !== '' && mb_strlen($name) <= 80) {
            Database::run('UPDATE tags SET name = ?, slug = ? WHERE id = ?', [$name, slugify($name), $id]);
            ModLog::log('update', 'tag', $id, $name);
            Session::flash('success', 'Tag renommé.');
        }
        Response::redirect('/admin/taxonomy');
    }

    public function tagDelete(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        if ($id > 0) {
            Database::run('DELETE FROM exhibition_tags WHERE tag_id = ?', [$id]);
            Database::run('DELETE FROM tags WHERE id = ?', [$id]);
            ModLog::log('delete', 'tag', $id);
            Session::flash('success', 'Tag supprimé.');
        }
        Response::redirect('/admin/taxonomy');
    }

    // ------------------------------------------------------------ settings & stats

    public function settings(array $params = []): void
    {
        View::render('admin/settings', [
            'title' => 'Paramètres',
            'settings' => Database::run('SELECT * FROM settings ORDER BY `key`')->fetchAll(),
        ]);
    }

    public function settingsUpdate(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        foreach ((array) Request::input('settings', []) as $key => $value) {
            $key = mb_substr(trim((string) $key), 0, 100);
            if ($key === '') {
                continue;
            }
            Database::run(
                'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
                [$key, mb_substr((string) $value, 0, 2000)]
            );
        }
        ModLog::log('update', 'settings', null);
        Session::flash('success', 'Paramètres enregistrés.');
        Response::redirect('/admin/settings');
    }

    public function stats(array $params = []): void
    {
        View::render('admin/stats', [
            'title' => 'Statistiques',
            'counters' => [
                'exhibitions_publiees' => (int) Database::run('SELECT COUNT(*) FROM exhibitions WHERE status = "published"')->fetchColumn(),
                'exhibitions_archivees' => (int) Database::run('SELECT COUNT(*) FROM exhibitions WHERE status = "archived"')->fetchColumn(),
                'lieux' => (int) Database::run('SELECT COUNT(*) FROM places WHERE status = "published"')->fetchColumn(),
                'artistes' => (int) Database::run('SELECT COUNT(*) FROM artists WHERE status = "published"')->fetchColumn(),
                'membres' => (int) Database::run('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn(),
                'newsletter' => (int) Database::run('SELECT COUNT(*) FROM newsletter_subscribers WHERE confirmed = 1')->fetchColumn(),
                'vues' => (int) Database::run('SELECT COALESCE(SUM(view_count),0) FROM exhibitions')->fetchColumn(),
                'favoris' => (int) Database::run('SELECT COUNT(*) FROM favorites')->fetchColumn(),
            ],
            'byMonth' => \App\Expositions\ExhibitionModel::statsByMonth(12),
            'byCategory' => \App\Expositions\ExhibitionModel::statsByCategory(),
            'byCommune' => \App\Expositions\ExhibitionModel::statsByCommune(),
            'moderation' => Database::run(
                'SELECT u.username, COUNT(*) n, ROUND(AVG(TIMESTAMPDIFF(HOUR, e.created_at, e.moderated_at)),1) avg_h
                 FROM exhibitions e JOIN users u ON u.id = e.moderated_by
                 WHERE e.moderated_at IS NOT NULL GROUP BY u.id ORDER BY n DESC'
            )->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ communes (référentiel)

    public function communes(array $params = []): void
    {
        View::render('admin/communes', [
            'title' => 'Référentiel des communes',
            'rows' => Database::run('SELECT c.*, (SELECT COUNT(*) FROM exhibitions e WHERE e.commune_id = c.id) n_expos FROM communes c ORDER BY c.nom')->fetchAll(),
        ]);
    }
}
