<?php

/**
 * Déclaration centralisée des routes (ordre : statiques AVANT dynamiques).
 */

use Core\Router;
use App\Auth\AuthController;
use App\Expositions\ExhibitionController;
use App\Expositions\CalendarController;
use App\Places\PlaceController;
use App\Artists\ArtistController;
use App\Moderation\ModerationController;
use App\Community\CommunityController;
use App\Community\IcalController;
use App\Admin\AdminController;
use App\HomeController;

return function (Router $r): void {

    // --- Accueil / découverte ---
    $r->get('/', [HomeController::class, 'index'], 'home');
    $r->get('/recherche/suggest.json', [HomeController::class, 'searchSuggest'], 'search.suggest');
    $r->get('/carte/data.json', [HomeController::class, 'mapData'], 'map.data');
    $r->get('/carte', [\App\Expositions\MapController::class, 'index'], 'map');
    $r->get('/calendrier', [CalendarController::class, 'month'], 'calendar');

    // --- Authentification ---
    $r->get('/login', [AuthController::class, 'showLogin'], 'login');
    $r->post('/login', [AuthController::class, 'login']);
    $r->get('/logout', [AuthController::class, 'logout'], 'logout');
    $r->get('/register', [AuthController::class, 'showRegister'], 'register');
    $r->post('/register', [AuthController::class, 'register']);
    $r->get('/register/confirm', [AuthController::class, 'confirmRegister']);
    $r->get('/password/forgot', [AuthController::class, 'showForgot']);
    $r->post('/password/forgot', [AuthController::class, 'sendReset']);
    $r->get('/password/reset', [AuthController::class, 'showReset']);
    $r->post('/password/reset', [AuthController::class, 'reset']);
    $r->get('/profile', [AuthController::class, 'showProfile'])->middleware('auth');
    $r->post('/profile', [AuthController::class, 'updateProfile'])->middleware('auth');
    $r->post('/profile/password', [AuthController::class, 'updatePassword'])->middleware('auth');

    // --- Expositions (statiques d'abord) ---
    $r->get('/expositions', [ExhibitionController::class, 'index'], 'expositions');
    $r->get('/expositions/propose', [ExhibitionController::class, 'proposeForm'], 'expositions.propose');
    $r->post('/expositions/propose', [ExhibitionController::class, 'propose']);
    $r->get('/expositions/create', [ExhibitionController::class, 'create'], 'expositions.create')->middleware('contributor');
    $r->post('/expositions/create', [ExhibitionController::class, 'store'])->middleware('contributor');
    $r->get('/expositions/{id}/edit', [ExhibitionController::class, 'edit'])->middleware('auth');
    $r->post('/expositions/{id}/edit', [ExhibitionController::class, 'update'])->middleware('auth');
    $r->post('/expositions/{id}/duplicate', [ExhibitionController::class, 'duplicate'])->middleware('auth');
    $r->post('/expositions/{id}/delete', [ExhibitionController::class, 'delete'])->middleware('auth');
    $r->post('/expositions/{id}/events', [ExhibitionController::class, 'eventStore'])->middleware('auth');
    $r->get('/expositions/{id}/events/new', [ExhibitionController::class, 'eventForm'])->middleware('auth');
    $r->post('/expositions/events/{id}/delete', [ExhibitionController::class, 'eventDelete'])->middleware('auth');
    $r->get('/expositions/{slug}', [ExhibitionController::class, 'show'], 'expositions.show');

    // --- Lieux ---
    $r->get('/lieux', [PlaceController::class, 'index'], 'places');
    $r->get('/lieux/create', [PlaceController::class, 'create'], 'places.create')->middleware('auth');
    $r->post('/lieux/create', [PlaceController::class, 'store'])->middleware('auth');
    $r->get('/lieux/{id}/edit', [PlaceController::class, 'edit'])->middleware('auth');
    $r->post('/lieux/{id}/edit', [PlaceController::class, 'update'])->middleware('auth');
    $r->get('/lieux/{slug}', [PlaceController::class, 'show'], 'places.show');

    // --- Artistes ---
    $r->get('/artistes', [ArtistController::class, 'index'], 'artists');
    $r->get('/artistes/create', [ArtistController::class, 'create'], 'artists.create')->middleware('auth');
    $r->post('/artistes/create', [ArtistController::class, 'store'])->middleware('auth');
    $r->post('/artistes/{id}/claim', [ArtistController::class, 'claim'])->middleware('auth');
    $r->get('/artistes/{id}/edit', [ArtistController::class, 'edit'])->middleware('auth');
    $r->post('/artistes/{id}/edit', [ArtistController::class, 'update'])->middleware('auth');
    $r->get('/artistes/{slug}', [ArtistController::class, 'show'], 'artists.show');

    // --- Modération ---
    $r->get('/moderation', [ModerationController::class, 'queue'])->middleware('moderator');
    $r->post('/moderation/expositions/{id}/approve', [ModerationController::class, 'approveExhibition'])->middleware('moderator');
    $r->post('/moderation/expositions/{id}/reject', [ModerationController::class, 'rejectExhibition'])->middleware('moderator');
    $r->post('/moderation/expositions/{id}/unpublish', [ModerationController::class, 'unpublishExhibition'])->middleware('moderator');
    $r->post('/moderation/lieux/{id}/approve', [ModerationController::class, 'approvePlace'])->middleware('moderator');
    $r->post('/moderation/lieux/{id}/reject', [ModerationController::class, 'rejectPlace'])->middleware('moderator');
    $r->post('/moderation/artistes/{id}/approve', [ModerationController::class, 'approveArtist'])->middleware('moderator');
    $r->post('/moderation/artistes/{id}/reject', [ModerationController::class, 'rejectArtist'])->middleware('moderator');
    $r->post('/moderation/claims/{id}/approve', [ModerationController::class, 'approveClaim'])->middleware('moderator');
    $r->post('/moderation/claims/{id}/reject', [ModerationController::class, 'rejectClaim'])->middleware('moderator');
    $r->post('/moderation/suggestions/{id}/resolve', [ModerationController::class, 'resolveSuggestion'])->middleware('moderator');
    $r->post('/moderation/reports/{id}/resolve', [ModerationController::class, 'resolveReport'])->middleware('moderator');

    // --- Communauté ---
    $r->post('/favoris/toggle', [CommunityController::class, 'toggleFavorite'])->middleware('auth');
    $r->post('/visites/toggle', [CommunityController::class, 'toggleVisit'])->middleware('auth');
    $r->post('/signaler', [CommunityController::class, 'report']);
    $r->post('/newsletter/subscribe', [CommunityController::class, 'newsletterSubscribe']);
    $r->get('/newsletter/confirm', [CommunityController::class, 'newsletterConfirm']);
    $r->get('/newsletter/unsubscribe', [CommunityController::class, 'newsletterUnsubscribe']);
    $r->get('/ical/exposition/{slug}', [IcalController::class, 'exhibition']);
    $r->get('/ical/agenda', [IcalController::class, 'agenda']);

    // --- Admin ---
    $r->get('/admin/users', [AdminController::class, 'users'])->middleware('admin');
    $r->get('/admin/users/{id}/edit', [AdminController::class, 'userEdit'])->middleware('admin');
    $r->post('/admin/users/{id}/edit', [AdminController::class, 'userUpdate'])->middleware('admin');
    $r->get('/admin/taxonomy', [AdminController::class, 'taxonomy'])->middleware('admin');
    $r->post('/admin/taxonomy/categories', [AdminController::class, 'categoryStore'])->middleware('admin');
    $r->post('/admin/taxonomy/categories/{id}', [AdminController::class, 'categoryUpdate'])->middleware('admin');
    $r->post('/admin/taxonomy/tags/merge', [AdminController::class, 'tagMerge'])->middleware('admin');
    $r->post('/admin/taxonomy/tags/{id}/approve', [AdminController::class, 'tagApprove'])->middleware('admin');
    $r->post('/admin/taxonomy/tags/create', [AdminController::class, 'tagStore'])->middleware('admin');
    $r->post('/admin/taxonomy/tags/{id}/edit', [AdminController::class, 'tagUpdate'])->middleware('admin');
    $r->post('/admin/taxonomy/tags/{id}/delete', [AdminController::class, 'tagDelete'])->middleware('admin');
    $r->get('/admin/settings', [AdminController::class, 'settings'])->middleware('admin');
    $r->post('/admin/settings', [AdminController::class, 'settingsUpdate'])->middleware('admin');
    $r->get('/admin/stats', [AdminController::class, 'stats'])->middleware('admin');
    $r->get('/admin/communes', [AdminController::class, 'communes'])->middleware('admin');

    // --- Mon espace (tableau de bord contributeur) ---
    $r->get('/mon-espace', [\App\Auth\DashboardController::class, 'index'])->middleware('auth');
};