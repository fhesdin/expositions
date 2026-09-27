<?php

/** Fonctions globales (helpers) disponibles dans tout le projet. */

/**
 * Fonctions utilitaires globales.
 * Chargé via composer "files" ou require explicite.
 */

/** Échappe du HTML pour l'affichage. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Génère une URL absolue à partir d'un chemin relatif. */
function url(string $path = ''): string
{
    $base = rtrim(\Core\Env::get('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Génère une URL de route au format ?r=/path */
function route(string $path = ''): string
{
    return '?r=/' . ltrim($path, '/');
}

/** Redirige et termine. */
function redirect(string $path, int $code = 302): void
{
    header('Location: ' . $path, true, $code);
    exit;
}

/** Génère un slug à partir d'un texte. */
function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'item';
}

/** Formate une date pour affichage FR. */
function date_fr(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if ($datetime === null) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '' : date($format, $ts);
}

/** Tronque un texte. */
function excerpt(?string $text, int $length = 120): string
{
    if ($text === null) {
        return '';
    }
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '…';
}

/** Formatage de lat/lng. */
function coord(float $v): string
{
    return number_format($v, 5, '.', '');
}

/** Génère un UUID v4. */
function uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

/** Vérifie qu'une chaîne est un UUID. */
function is_uuid(string $s): bool
{
    return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $s);
}
