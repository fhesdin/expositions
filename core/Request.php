<?php

namespace Core;

/**
 * Wrapper de la requête HTTP courante.
 */
final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function path(): string
    {
        $r = $_GET['r'] ?? null;
        if ($r !== null && $r !== '') {
            return '/' . ltrim($r, '/');
        }
        // Fallback: parsing de REQUEST_URI (pour Apache avec .htaccess)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = str_replace('\\', '/', dirname($scriptName));
        if ($base !== '/' && $base !== '.' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if ($uri === '' || $uri === false) {
            $uri = '/';
        }
        return $uri;
    }

    /** Retourne le chemin de base (toujours vide en mode query string). */
    public static function basePath(): string
    {
        return '';
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function all(): array
    {
        return $_POST;
    }

    public static function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        if ($f === null || $f['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public static function files(string $key): array
    {
        $f = $_FILES[$key] ?? null;
        if ($f === null) {
            return [];
        }
        if (!is_array($f['name'])) {
            return [$f];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => $f['type'][$i],
                'tmp_name' => $f['tmp_name'][$i],
                'error' => $f['error'][$i],
                'size' => $f['size'][$i],
            ];
        }
        return $out;
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
