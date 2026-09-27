<?php

namespace Core;

/**
 * Envoi d'e-mails via SMTP de l'hébergeur.
 * Utilise fsockopen pour éviter la dépendance à PHPMailer.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $bodyHtml, ?string $from = null, ?string $fromName = null): bool
    {
        $from = $from ?? Env::get('MAIL_FROM', 'noreply@example.com');
        $fromName = $fromName ?? Env::get('MAIL_FROM_NAME', 'Pokémon GO Manager');

        $host = Env::get('SMTP_HOST', '');
        $port = Env::getInt('SMTP_PORT', 587);
        $user = Env::get('SMTP_USER', '');
        $pass = Env::get('SMTP_PASS', '');
        $secure = Env::get('SMTP_SECURE', 'tls');

        if (Env::getBool('APP_DEBUG') && $host === '') {
            // En debug sans SMTP, on logge seulement.
            error_log("[MAIL] To: $to | Subject: $subject");
            return true;
        }

        try {
            $fp = fsockopen($host, $port, $errno, $errstr, 10);
            if (!$fp) {
                throw new \RuntimeException("$errstr ($errno)");
            }
            self::readLine($fp);

            self::cmd($fp, "EHLO " . gethostname());
            if ($secure === 'tls') {
                self::cmd($fp, "STARTTLS");
                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::cmd($fp, "EHLO " . gethostname());
            }
            if ($user !== '' && $pass !== '') {
                self::cmd($fp, "AUTH LOGIN");
                self::cmd($fp, base64_encode($user));
                self::cmd($fp, base64_encode($pass));
            }

            self::cmd($fp, "MAIL FROM: <$from>");
            self::cmd($fp, "RCPT TO: <$to>");
            self::cmd($fp, "DATA");

            $boundary = md5(uniqid());
            $headers = [
                "From: $fromName <$from>",
                "To: $to",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "Content-Transfer-Encoding: 8bit",
                "Date: " . date(DATE_RFC2822),
                "Message-ID: <" . uniqid() . "@" . gethostname() . ">",
                "Precedence: bulk",
            ];
            $msg = implode("\r\n", $headers) . "\r\n\r\n" . $bodyHtml . "\r\n.";
            self::cmd($fp, $msg);

            self::cmd($fp, "QUIT");
            fclose($fp);
            return true;
        } catch (\Throwable $e) {
            error_log("[MAIL ERROR] " . $e->getMessage());
            return false;
        }
    }

    private static function cmd($fp, string $command): string
    {
        fwrite($fp, $command . "\r\n");
        return self::readLine($fp);
    }

    private static function readLine($fp): string
    {
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 515);
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($code >= 400) {
            throw new \RuntimeException("SMTP: $response");
        }
        return $response;
    }
}
