<?php

namespace App;

use Core\View;

final class PagesController
{
    private const PAGES = [
        'a-propos' => ['À propos', 'pages/about'],
        'charte' => ['Charte de contribution', 'pages/charter'],
        'mentions-legales' => ['Mentions légales', 'pages/legal'],
        'confidentialite' => ['Confidentialité', 'pages/privacy'],
    ];

    public function show(array $params = []): void
    {
        $slug = (string) ($params['slug'] ?? '');
        if (!isset(self::PAGES[$slug])) {
            \Core\Response::notFound('Page introuvable.');
        }
        [$title, $view] = self::PAGES[$slug];
        View::render($view, ['title' => $title]);
    }
}
