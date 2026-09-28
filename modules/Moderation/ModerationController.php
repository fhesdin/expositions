<?php

namespace App\Moderation;

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

/**
 * File d'attente de modération : expositions, événements, lieux, artistes,
 * tags proposés, revendications, signalements et suggestions.
 */
final class ModerationController
{
    public function queue(array $params = []): void
    {
        $counts = [
            'exhibitions' => (int) Database::run('SELECT COUNT(*) FROM exhibitions WHERE status = "pending"')->fetchColumn(),
            'events' => (int) Database::run('SELECT COUNT(*) FROM exhibition_events WHERE status = "pending"')->fetchColumn(),
            'places' => (int) Database::run('SELECT COUNT(*) FROM places WHERE status = "pending"')->fetchColumn(),
            'artists' => (int) Database::run('SELECT COUNT(*) FROM artists WHERE status = "pending"')->fetchColumn(),
            'tags' => (int) Database::run('SELECT COUNT(*) FROM tags WHERE is_approved = 0')->fetchColumn(),
            'claims' => (int) Database::run('SELECT COUNT(*) FROM claims WHERE status = "pending"')->fetchColumn(),
            'reports' => (int) Database::run('SELECT COUNT(*) FROM reports WHERE status = "open"')->fetchColumn(),
            'suggestions' => (int) Database::run('SELECT COUNT(*) FROM suggestions WHERE status = "open"')->fetchColumn(),
            'structures' => (int) Database::run('SELECT COUNT(*) FROM structures WHERE status = "pending"')->fetchColumn(),
        ];

        $exhibitions = Database::run(
            'SELECT e.*, u.username AS submitter, p.name AS place_name, c.nom AS commune_name
             FROM exhibitions e
             LEFT JOIN users u ON u.id = e.submitted_by
             LEFT JOIN places p ON p.id = e.place_id
             LEFT JOIN communes c ON c.id = e.commune_id
             WHERE e.status = "pending"
             ORDER BY e.created_at ASC LIMIT 100'
        )->fetchAll();
        $places = Database::run(
            'SELECT p.*, c.nom AS commune_name, u.username AS submitter
             FROM places p
             LEFT JOIN communes c ON c.id = p.commune_id
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.status = "pending" ORDER BY p.created_at ASC LIMIT 50'
        )->fetchAll();
        $artists = Database::run(
            'SELECT a.*, c.nom AS commune_name, u.username AS submitter
             FROM artists a
             LEFT JOIN communes c ON c.id = a.commune_id
             LEFT JOIN users u ON u.id = a.created_by
             WHERE a.status = "pending" ORDER BY a.created_at ASC LIMIT 50'
        )->fetchAll();
        $claims = Database::run(
            'SELECT cl.*, u.username, CASE cl.kind WHEN "artist" THEN (SELECT name FROM artists WHERE id = cl.entity_id) ELSE (SELECT name FROM places WHERE id = cl.entity_id) END AS entity_name
             FROM claims cl LEFT JOIN users u ON u.id = cl.user_id
             WHERE cl.status = "pending" ORDER BY cl.created_at ASC LIMIT 50'
        )->fetchAll();
        $reports = Database::run(
            'SELECT r.*, u.username FROM reports r LEFT JOIN users u ON u.id = r.user_id
             WHERE r.status = "open" ORDER BY r.created_at ASC LIMIT 50'
        )->fetchAll();
        $suggestions = Database::run(
            'SELECT * FROM suggestions WHERE status = "open" ORDER BY created_at ASC LIMIT 50'
        )->fetchAll();
        $structures = \App\Models\StructureModel::listPending();

        View::render('moderation/queue', [
            'title' => 'Modération',
            'counts' => $counts,
            'exhibitions' => $exhibitions,
            'places' => $places,
            'artists' => $artists,
            'claims' => $claims,
            'reports' => $reports,
            'structures' => $structures,
            'suggestions' => $suggestions,
            'journal' => ModLog::recent(20),
        ]);
    }

    // ------------------------------------------------------------ expositions

    public function approveExhibition(array $params = []): void
    {
        $this->moderateExhibition($params, 'published');
    }

    public function rejectExhibition(array $params = []): void
    {
        $this->moderateExhibition($params, 'rejected');
    }

    private function moderateExhibition(array $params, string $status): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $row = Database::run('SELECT * FROM exhibitions WHERE id = ?', [$id])->fetch();
        if ($row === false) {
            Response::notFound();
        }
        $reason = trim((string) Request::input('reason'));
        if ($status === 'rejected' && $reason === '') {
            Session::flash('error', 'Un refus doit être motivé.');
            Response::redirect('/moderation');
        }
        Database::run(
            'UPDATE exhibitions SET status = ?, moderated_by = ?, moderated_at = NOW(), reject_reason = ? WHERE id = ?',
            [$status, Auth::id(), $status === 'rejected' ? $reason : null, $id]
        );
        ModLog::log($status === 'published' ? 'approve' : 'reject', 'exhibition', $id, $reason ?: null);

        // notification du contributeur (e-mail si compte connu)
        if (!empty($row['submitted_by'])) {
            $u = Database::run('SELECT email, username FROM users WHERE id = ?', [$row['submitted_by']])->fetch();
            if ($u) {
                $msg = $status === 'published'
                    ? 'Bonne nouvelle : votre fiche « ' . e($row['title']) . ' » a été validée et est en ligne.'
                    : 'Votre fiche « ' . e($row['title']) . ' » a été refusée. Motif : ' . e($reason);
                \App\Auth\MailService::sendContactMessage($u['email'], 'Modération de votre fiche — expositions.top', '<p>' . $msg . '</p>');
            }
        }
        Session::flash('success', 'Fiche ' . ($status === 'published' ? 'validée' : 'refusée') . '.');
        Response::redirect('/moderation');
    }

    public function unpublishExhibition(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        Database::run('UPDATE exhibitions SET status = "pending" WHERE id = ?', [$id]);
        ModLog::log('depublier', 'exhibition', $id);
        Session::flash('success', 'Fiche dépubliée (retour en attente).');
        Response::redirect('/moderation');
    }

    // ------------------------------------------------------------ lieux & artistes

    public function approvePlace(array $params = []): void
    {
        $this->moderateSimple('places', (int) ($params['id'] ?? 0), 'published', 'place');
    }

    public function rejectPlace(array $params = []): void
    {
        $this->moderateSimple('places', (int) ($params['id'] ?? 0), 'rejected', 'place');
    }

    public function approveArtist(array $params = []): void
    {
        $this->moderateSimple('artists', (int) ($params['id'] ?? 0), 'published', 'artist');
    }

    public function rejectArtist(array $params = []): void
    {
        $this->moderateSimple('artists', (int) ($params['id'] ?? 0), 'rejected', 'artist');
    }

    private function moderateSimple(string $table, int $id, string $status, string $kind): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        Database::run("UPDATE $table SET status = ? WHERE id = ?", [$status, $id]);
        ModLog::log($status === 'published' ? 'approve' : 'reject', $kind, $id);
        Session::flash('success', ucfirst($kind) . ' ' . ($status === 'published' ? 'validé(e)' : 'refusé(e)') . '.');
        Response::redirect('/moderation');
    }

    // ------------------------------------------------------------ revendications

    public function approveClaim(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $claim = Database::run('SELECT * FROM claims WHERE id = ?', [$id])->fetch();
        if ($claim === false) {
            Response::notFound();
        }
        if ($claim['kind'] === 'artist') {
            Database::run('UPDATE artists SET user_id = ?, artist_verified = 1 WHERE id = ?', [$claim['user_id'], $claim['entity_id']]);
            Database::run('UPDATE users SET role_id = 3 WHERE id = ? AND role_id < 3', [$claim['user_id']]);
        } else {
            Database::run(
                'INSERT IGNORE INTO place_managers (place_id, user_id, is_primary)
                 SELECT ?, user_id, 0 FROM claims WHERE id = ?',
                [$claim['entity_id'], $id]
            );
            Database::run('UPDATE users SET role_id = 3 WHERE id = ? AND role_id < 3', [$claim['user_id']]);
        }
        Database::run('UPDATE claims SET status = "approved", moderator_id = ? WHERE id = ?', [Auth::id(), $id]);
        ModLog::log('claim.approve', $claim['kind'], (int) $claim['entity_id']);
        Session::flash('success', 'Revendication approuvée.');
        Response::redirect('/moderation');
    }

    public function rejectClaim(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        Database::run('UPDATE claims SET status = "rejected", moderator_id = ?, moderator_note = ? WHERE id = ?', [Auth::id(), trim((string) Request::input('reason')), $id]);
        ModLog::log('claim.reject', 'claim', $id);
        Session::flash('success', 'Revendication refusée.');
        Response::redirect('/moderation');
    }

    // ------------------------------------------------------------ suggestions & signalements

    public function resolveSuggestion(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $action = (string) Request::input('action', 'resolve');
        Database::run('UPDATE suggestions SET status = ?, moderator_id = ? WHERE id = ?', [$action === 'dismiss' ? 'dismissed' : 'resolved', Auth::id(), $id]);
        ModLog::log('suggestion.' . $action, 'suggestion', $id);
        Session::flash('success', 'Suggestion traitée.');
        Response::redirect('/moderation');
    }

    public function approveStructure(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $action = (string) Request::input('action', 'approve');
        if ($action === 'reject') {
            \App\Models\StructureModel::setStatus($id, 'archived');
            ModLog::log('structure.reject', 'structure', $id);
            Session::flash('info', 'Structure refusée.');
        } else {
            $row = \App\Models\StructureModel::find($id);
            \App\Models\StructureModel::setStatus($id, 'published');
            // le créateur devient admin de la structure (si pas déjà)
            if ($row && !empty($row['created_by'])) {
                Database::run(
                    'INSERT INTO structure_members (structure_id, user_id, role, status, requested_by, joined_at)
                     VALUES (?,?,"admin","active","user",NOW())
                     ON DUPLICATE KEY UPDATE role = IF(role="admin", role, role)',
                    [$id, (int) $row['created_by']]
                );
            }
            ModLog::log('structure.approve', 'structure', $id);
            Session::flash('success', 'Structure publiée.');
        }
        Response::redirect('/moderation');
    }

    public function resolveReport(array $params = []): void
    {
        if (!Csrf::check()) {
            Response::forbidden('Jeton CSRF invalide.');
        }
        $id = (int) ($params['id'] ?? 0);
        $action = (string) Request::input('action', 'resolve');
        $report = Database::run('SELECT * FROM reports WHERE id = ?', [$id])->fetch();
        if ($report !== false) {
            if ($action === 'dismiss') {
                Database::run('UPDATE reports SET status = "dismissed", moderator_id = ? WHERE id = ?', [Auth::id(), $id]);
            } else {
                // résoudre = masquer le contenu signalé (selon type)
                match ($report['kind']) {
                    'exhibition' => Database::run('UPDATE exhibitions SET status = "pending" WHERE id = ?', [$report['item_id']]),
                    'event' => Database::run('UPDATE exhibition_events SET status = "pending" WHERE id = ?', [$report['item_id']]),
                    'artist' => Database::run('UPDATE artists SET status = "hidden" WHERE id = ?', [$report['item_id']]),
                    'place' => Database::run('UPDATE places SET status = "hidden" WHERE id = ?', [$report['item_id']]),
                    default => null,
                };
                Database::run('UPDATE reports SET status = "resolved", moderator_id = ? WHERE id = ?', [Auth::id(), $id]);
            }
            ModLog::log('report.' . $action, $report['kind'], (int) $report['item_id']);
        }
        Session::flash('success', 'Signalement traité.');
        Response::redirect('/moderation');
    }
}
