<?php

namespace App\Expositions;

use Core\View;

/** Vue carte Leaflet plein écran (OpenStreetMap, clusters simples). */
final class MapController
{
    public function index(array $params = []): void
    {
        View::render('expositions/map', [
            'title' => 'Carte des expositions',
            'categories' => \App\Models\Category::allActive(),
            'leaflet' => true,
        ]);
    }
}
