<?php

namespace App\Auth;

use Core\Database;
use Core\View;

/** Tableau de bord contributeur : fiches par statut, gestion lieux/artistes. */
final class DashboardController
{
    public function index(array $params = []): void
    {
        $userId = (int) \Core\Auth::id();
        $isAdmin = \Core\Auth::isModerator();

        $sql = $isAdmin
            ? 'SELECT e.*, p.name AS place_name FROM exhibitions e LEFT JOIN places p ON p.id = e.place_id
               WHERE e.status IN ("draft","pending","rejected") OR e.submitted_by = ?
               ORDER BY e.updated_at DESC LIMIT 100'
            : 'SELECT e.*, p.name AS place_name FROM exhibitions e LEFT JOIN places p ON p.id = e.place_id
               WHERE e.submitted_by = ?
               ORDER BY e.updated_at DESC LIMIT 100';
        $expos = Database::run($sql, [$userId])->fetchAll();

        $managedPlaces = \App\Places\PlaceModel::managedBy($userId);
        $artistProfile = \App\Artists\ArtistModel::forUser($userId);

        View::render('auth/dashboard', [
            'title' => 'Mon espace',
            'expos' => $expos,
            'managedPlaces' => $managedPlaces,
            'artistProfile' => $artistProfile,
            'agenda' => \App\Community\AgendaModel::forUser($userId),
            'favorites' => \App\Community\FavoriteModel::forUser($userId),
            'icalUrl' => \App\Community\IcalController::agendaUrl($userId),
        ]);
    }
}