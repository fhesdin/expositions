<?php

namespace App\Community;

use Core\Csrf;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

/**
 * Actions communautaires : favoris, J'y vais, newsletter, signalements.
 */
final class CommunityController
{
    public function toggleFavorite(array $params = []): void
    {
        if (!\Core\Auth::check()) {
            Response::forbidden('Connectez-vous pour utiliser les favoris.');
        }
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $kind = (string) Request::input('kind');
        $itemId = (int) Request::input('item_id');
        $allowed = ['exhibition', 'artist', 'place'];
        if (!in_array($kind, $allowed, true) || $itemId <= 0) {
            Response::forbidden('Paramètres invalides.');
        }
        $now = FavoriteModel::toggle(\Core\Auth::id(), $kind, $itemId);
        if (self::wantsJson()) {
            Response::json(['success' => true, 'favorite' => $now]);
        }
        Session::flash('success', $now ? 'Ajouté aux favoris.' : 'Retiré des favoris.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public function toggleVisit(array $params = []): void
    {
        if (!\Core\Auth::check()) {
            Response::forbidden('Connectez-vous pour indiquer votre participation.');
        }
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $which = (string) Request::input('which') === 'went' ? 'went' : 'going';
        $exhibitionId = (int) Request::input('exhibition_id');
        if ($exhibitionId <= 0) {
            Response::forbidden('Paramètres invalides.');
        }
        VisitModel::toggle(\Core\Auth::id(), $exhibitionId, $which);
        if (self::wantsJson()) {
            Response::json(['success' => true, 'counts' => VisitModel::counts($exhibitionId)]);
        }
        Session::flash('success', 'Participation enregistrée.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public function report(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $kind = (string) Request::input('kind');
        $itemId = (int) Request::input('item_id');
        $reason = trim((string) Request::input('reason'));
        if (!in_array($kind, ['exhibition', 'event', 'artist', 'place', 'comment'], true) || $reason === '') {
            Session::flash('error', 'Le motif du signalement est obligatoire.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        Database::run(
            'INSERT INTO reports (kind, item_id, user_id, email, reason) VALUES (?, ?, ?, ?, ?)',
            [$kind, $itemId, \Core\Auth::id(), \Core\Auth::user()['email'] ?? null, mb_substr($reason, 0, 500)]
        );
        Session::flash('success', 'Signalement transmis à la modération. Merci !');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    // ------------------------------------------------------------ newsletter (sans compte)

    public function newsletterSubscribe(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $email = strtolower(trim((string) Request::input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Adresse e-mail invalide.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        $token = bin2hex(random_bytes(32));
        $existing = Database::run('SELECT id, confirmed FROM newsletter_subscribers WHERE email = ?', [$email])->fetch();
        if ($existing === false) {
            Database::run(
                'INSERT INTO newsletter_subscribers (email, confirm_token, unsubscribe_token, confirmed) VALUES (?, ?, ?, 0)',
                [$email, $token, bin2hex(random_bytes(32))]
            );
        } elseif (!(bool) $existing['confirmed']) {
            Database::run('UPDATE newsletter_subscribers SET confirm_token = ? WHERE id = ?', [$token, $existing['id']]);
        } else {
            Session::flash('info', 'Cette adresse est déjà abonnée à la newsletter.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        $link = rtrim(\Core\Env::get('APP_URL', ''), '/') . '?r=/newsletter/confirm&token=' . urlencode($token);
        \App\Auth\MailService::sendContactMessage($email, 'Confirmez votre inscription à la newsletter — expositions.top',
            '<p>Bonjour,</p><p>Cliquez pour confirmer votre abonnement à la newsletter d\'expositions.top :</p><p><a href="' . e($link) . '">' . e($link) . '</a></p><p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message.</p>');
        Session::flash('success', 'Inscription enregistrée : confirmez via l\'e-mail envoyé (double confirmation).');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public function newsletterConfirm(array $params = []): void
    {
        $token = (string) \Core\Request::query('token', '');
        $row = $token !== '' ? Database::run('SELECT id FROM newsletter_subscribers WHERE confirm_token = ?', [$token])->fetch() : false;
        if ($row !== false) {
            Database::run('UPDATE newsletter_subscribers SET confirmed = 1, confirm_token = NULL WHERE id = ?', [$row['id']]);
        }
        View::render('community/newsletter_confirm', ['ok' => $row !== false, 'title' => 'Newsletter']);
    }

    public function newsletterUnsubscribe(array $params = []): void
    {
        $token = (string) \Core\Request::query('token', '');
        $row = $token !== '' ? Database::run('SELECT id FROM newsletter_subscribers WHERE unsubscribe_token = ?', [$token])->fetch() : false;
        if ($row !== false) {
            Database::run('DELETE FROM newsletter_subscribers WHERE id = ?', [$row['id']]);
        }
        View::render('community/newsletter_unsub', ['ok' => $row !== false, 'title' => 'Désinscription']);
    }

    private static function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest');
    }
}
