<?php

namespace App\Auth;

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Validator;
use Core\View;

final class AuthController
{
    public function showLogin(array $params = []): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }
        View::render('auth/login', ['email' => '', 'error' => null, 'title' => 'Connexion']);
    }

    public function login(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $identifier = trim((string) Request::input('identifier', (string) Request::input('email', '')));
        $password = (string) Request::input('password');
        $remember = (bool) Request::input('remember');

        if (!Auth::attempt($identifier, $password, $remember)) {
            View::render('auth/login', ['form' => ['identifier' => $identifier], 'error' => 'Identifiants invalides ou compte inactif.', 'title' => 'Connexion']);
            return;
        }
        Session::flash('success', 'Connexion réussie. Bonjour ' . e(Auth::user()['username']) . ' !');
        Response::redirect('/');
    }

    public function logout(array $params = []): void
    {
        Auth::logout();
        Response::redirect('/');
    }

    // ------------------------------------------------------------ inscription

    public function showRegister(array $params = []): void
    {
        View::render('auth/register', [
            'title' => 'Créer un compte',
            'form' => ['email' => '', 'username' => ''],
            'errors' => [],
            'communes' => \App\Models\Commune::allOrdered(),
        ]);
    }

    public function register(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $v = Validator::make($_POST)
            ->required('email')->email('email')
            ->required('username')->min('username', 3)->max('username', 60)
            ->required('password')->min('password', 8, 'Le mot de passe doit faire au moins 8 caractères.')
            ->required('cgu', 'Vous devez accepter les CGU et la charte de contribution.');

        $email = strtolower(trim((string) Request::input('email')));
        $username = trim((string) Request::input('username'));
        $errors = $v->errors();

        if (UserModel::emailExists($email)) {
            $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
        }
        if (UserModel::usernameExists($username)) {
            $errors['username'] = 'Ce pseudonyme est déjà pris.';
        }

        if ($errors !== []) {
            View::render('auth/register', [
                'title' => 'Créer un compte',
                'form' => ['email' => $email, 'username' => $username],
                'errors' => $errors,
                'communes' => \App\Models\Commune::allOrdered(),
            ]);
            return;
        }

        $token = bin2hex(random_bytes(32));
        UserModel::create([
            'email' => $email,
            'username' => $username,
            'password_hash' => password_hash((string) Request::input('password'), PASSWORD_DEFAULT),
            'role_id' => 2,
            'commune_id' => (int) Request::input('commune_id') ?: null,
            'profile_kind' => in_array((string) Request::input('profile_kind'), ['membre', 'artiste', 'lieu'], true) ? (string) Request::input('profile_kind') : 'membre',
            'confirm_token' => $token,
        ]);

        MailService::sendConfirmRegistration($email, $username, $token);
        Session::flash('success', 'Compte créé ! Vérifiez votre boîte mail pour confirmer votre adresse.');
        Response::redirect('/login');
    }

    public function confirmRegister(array $params = []): void
    {
        $token = (string) (\Core\Request::query('token') ?? '');
        $ok = $token !== '' && UserModel::confirmEmail($token);
        View::render('auth/confirm', ['title' => 'Confirmation', 'ok' => $ok]);
    }

    // ------------------------------------------------- mot de passe oublié

    public function showForgot(array $params = []): void
    {
        View::render('auth/forgot', ['title' => 'Mot de passe oublié', 'sent' => false, 'sentEmail' => '', 'error' => null]);
    }

    public function sendReset(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $email = strtolower(trim((string) Request::input('email')));
        $user = UserModel::findByEmail($email);
        $sent = false;
        if ($user !== null && (bool) $user['is_active']) {
            $token = bin2hex(random_bytes(32));
            \App\Models\PasswordReset::issue((int) $user['id'], $token);
            $link = rtrim(\Core\Env::get('APP_URL', ''), '/') . '?r=/password/reset&token=' . urlencode($token);
            MailService::sendContactMessage($email, 'Réinitialisation de votre mot de passe — expositions.top',
                '<p>Bonjour,</p><p>Cliquez sur ce lien pour choisir un nouveau mot de passe (valable 2 heures) :</p><p><a href="' . e($link) . '">' . e($link) . '</a></p>');
            $sent = true;
        }
        View::render('auth/forgot', ['title' => 'Mot de passe oublié', 'sent' => $sent, 'sentEmail' => $email, 'error' => null]);
    }

    public function showReset(array $params = []): void
    {
        $token = (string) (\Core\Request::query('token') ?? '');
        View::render('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'error' => null]);
    }

    public function reset(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $token = (string) Request::input('token');
        $row = \App\Models\PasswordReset::findValid($token);
        if ($row === null) {
            View::render('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'error' => 'Lien invalide ou expiré.']);
            return;
        }
        $password = (string) Request::input('password');
        if (mb_strlen($password) < 8) {
            View::render('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'error' => 'Mot de passe trop court (8 caractères minimum).']);
            return;
        }
        Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [
            password_hash($password, PASSWORD_DEFAULT), $row['user_id'],
        ]);
        \App\Models\PasswordReset::consume((int) $row['id']);
        Session::flash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');
        Response::redirect('/login');
    }

    // ------------------------------------------------------------ profil

    public function showProfile(array $params = []): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        $user = Auth::user();
        View::render('auth/profile', [
            'title' => 'Mon profil',
            'profile' => $user,
            'favorites' => \App\Community\FavoriteModel::forUser((int) $user['id']),
            'communes' => \App\Models\Commune::allOrdered(),
            'managedPlaces' => \App\Places\PlaceModel::managedBy((int) $user['id']),
            'artistProfile' => \App\Artists\ArtistModel::forUser((int) $user['id']),
            'agenda' => \App\Community\AgendaModel::forUser((int) $user['id']),
        ]);
    }

    public function updateProfile(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        if (!Auth::check()) {
            Response::forbidden('Non connecté.');
        }
        $userId = Auth::id();
        $username = trim((string) Request::input('username'));
        $communeId = (int) Request::input('commune_id') ?: null;

        $errors = [];
        if (mb_strlen($username) < 3) {
            $errors['username'] = 'Pseudonyme trop court.';
        } elseif (UserModel::usernameExists($username, $userId)) {
            $errors['username'] = 'Pseudonyme déjà pris.';
        }
        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect('/profile');
        }
        $interests = array_values(array_filter(array_map('intval', (array) Request::input('interests', []))));
        UserModel::update($userId, [
            'username' => $username,
            'commune_id' => $communeId,
            'interests' => json_encode($interests, JSON_UNESCAPED_UNICODE),
            'notify_email' => (bool) Request::input('notify_email') ? 1 : 0,
            'notify_weekly' => (bool) Request::input('notify_weekly') ? 1 : 0,
        ]);
        Session::flash('success', 'Profil mis à jour.');
        Response::redirect('/profile');
    }

    public function updatePassword(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $user = Auth::user();
        if ($user === null) {
            Response::forbidden('Non connecté.');
        }
        $current = (string) Request::input('current_password');
        $new = (string) Request::input('new_password');
        if (!password_verify($current, (string) $user['password_hash']) || mb_strlen($new) < 8) {
            Session::flash('error', 'Mot de passe actuel incorrect ou nouveau mot de passe trop court (8 caractères min).');
            Response::redirect('/profile');
        }
        UserModel::update((int) $user['id'], ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
        Session::flash('success', 'Mot de passe modifié.');
        Response::redirect('/profile');
    }
}