<?php

namespace App\Auth;

use Core\Database;
use Core\Mailer;
use Core\Session;
use Core\View;

/**
 * Envoi d'e-mails transactionnels (confirmation, modération…).
 * En APP_DEBUG sans SMTP, Mailer logge seulement.
 */
final class MailService
{
    public static function sendConfirmRegistration(string $to, string $username, string $token): void
    {
        $link = rtrim(\Core\Env::get('APP_URL', ''), '/') . '/?r=/register/confirm&token=' . urlencode($token);
        $body = View::render('emails/confirm_registration', ['username' => $username, 'link' => $link], null);
        Mailer::send($to, 'Confirmez votre inscription — expositions.top', $body);
    }

    public static function sendContactMessage(string $to, string $subject, string $bodyHtml): void
    {
        Mailer::send($to, $subject, $bodyHtml);
    }

    public static function notifyModerators(string $subject, string $bodyHtml): void
    {
        $rows = Database::run(
            'SELECT email FROM users WHERE role_id >= 4 AND is_active = 1'
        )->fetchAll();
        foreach ($rows as $r) {
            Mailer::send($r['email'], $subject, $bodyHtml);
        }
    }

    public static function flashMailQueued(string $to): void
    {
        Session::flash('info', 'E-mail de confirmation envoyé à ' . $to . ' (vérifiez vos spams).');
    }
}