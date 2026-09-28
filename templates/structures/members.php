<?php /** Gestion des membres d'une structure (admin structure / admin site) */ ?>
<div class="page-head"><h1>Membres — <?= e($s['nom']) ?></h1>
<p class="sub"><a href="?r=/structures/<?= e($s['slug']) ?>">← Retour à la fiche</a></p></div>

<section class="mod-section">
    <h2>Inviter un utilisateur</h2>
    <form method="post" action="?r=/structures/<?= (int) $s['id'] ?>/invite" class="filter-bar">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <div><label>Pseudo ou e-mail</label><input type="text" name="who" required placeholder="ex. fhesdin"></div>
        <button class="btn btn-primary" type="submit">Inviter</button>
    </form>
    <p class="muted small">L'invité devra confirmer son adhésion depuis la fiche publique de la structure.</p>
</section>

<section class="mod-section">
    <h2>Membres (<?= count($members) ?>)</h2>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td><b><?= e($m['username']) ?></b><br><span class="muted small"><?= e($m['email']) ?></span></td>
                    <td>
                        <form method="post" action="?r=/structures/<?= (int) $s['id'] ?>/members/<?= (int) $m['user_id'] ?>" class="filter-bar">
                            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                            <input type="hidden" name="action" value="role">
                            <select name="role" onchange="this.form.submit()">
                                <?php foreach (['member' => 'Membre', 'contributor' => 'Contributeur', 'admin' => 'Responsable'] as $rk => $rl): ?>
                                <option value="<?= e($rk) ?>" <?= ($m['role'] ?? '') === $rk ? 'selected' : '' ?>><?= e($rl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($m['status'] === 'pending'): ?>
                            <span class="badge"><?= e($m['requested_by'] === 'user' ? 'demande' : 'invitation') ?></span>
                        <?php elseif ($m['status'] === 'refused'): ?>
                            <span class="muted small">refusé</span>
                        <?php else: ?>
                            <span class="badge">actif</span>
                        <?php endif; ?>
                    </td>
                    <td class="mod-actions">
                        <?php if ($m['status'] === 'pending'): ?>
                        <form method="post" action="?r=/structures/<?= (int) $s['id'] ?>/members/<?= (int) $m['user_id'] ?>">
                            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="btn btn-sm btn-primary" type="submit">Confirmer</button>
                        </form>
                        <form method="post" action="?r=/structures/<?= (int) $s['id'] ?>/members/<?= (int) $m['user_id'] ?>">
                            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                            <input type="hidden" name="action" value="refuse">
                            <button class="btn btn-sm btn-outline" type="submit">Refuser</button>
                        </form>
                        <?php elseif ($m['status'] === 'active'): ?>
                        <form method="post" action="?r=/structures/<?= (int) $s['id'] ?>/members/<?= (int) $m['user_id'] ?>">
                            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                            <input type="hidden" name="action" value="remove">
                            <button class="btn btn-sm btn-danger" type="submit">Retirer</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
