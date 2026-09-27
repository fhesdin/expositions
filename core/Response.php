<?php

namespace Core;

/**
 * Réponse HTTP simple.
 */
final class Response
{
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $path, int $code = 302): void
    {
        if (str_starts_with($path, '/') && !str_starts_with($path, '//')) {
            $parts = explode('?', $path, 2);
            $path = '?r=' . $parts[0];
            if (isset($parts[1])) {
                $path .= '&' . $parts[1];
            }
        }
        header('Location: ' . $path, true, $code);
        exit;
    }

    public static function notFound(string $message = 'Page introuvable'): void
    {
        http_response_code(404);
        View::render('errors/404', ['message' => $message]);
        exit;
    }

    public static function forbidden(string $message = 'Accès refusé'): void
    {
        http_response_code(403);
        View::render('errors/403', ['message' => $message]);
        exit;
    }
}
