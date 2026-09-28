<?php /** Admin : liste utilisateurs */ ?>
<div class="page-head"><h1>Utilisateurs</h1></div>

<details class="mod-section">
    <summary><b>➕ Créer un utilisateur</b></summary>
    <form method="post" action="?r=/admin/users/create" class="filter-bar" style="flex-wrap:wrap;gap:.75rem;align-items:end">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <div><label>Pseudonyme *</label><input type="text" name="username" required minlength="3"></div>
        <div><label>E-mail *</label><input type="email" name="email" required></div>
        <div><label>Mot de passe * (10 car. min)</label><input type="text" name="password" required minlength="10" autocomplete="new-password"></div>
        <div><label>Rôle</label>
            <select name="role_id">
                <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?> (<?= (int) $r['rank'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Commune</label>
            <select name="commune_id">
                <option value="">—</option>
                <?php foreach (\App\Models\Commune::allOrdered() as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Créer</button>
    </form>
    <p class="muted small">Le compte est créé <b>actif et confirmé</b> : communiquez le mot de passe à l'intéressé, il pourra le modifier via « Mot de passe oublié ».</p>
</details>
<div class="table-wrap">
    <table class="list">
        <thead><tr><th>#</th><th>Pseudo</th><th>E-mail</th><th>Rôle</th><th>État</th><th>Dernière connexion</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><b><?= e($u['username']) ?></b><?php if ($u['artist_verified']): ?> <span class="badge encours">artiste ✓</span><?php endif; ?></td>
                <td class="small"><?= e($u['email']) ?></td>
                <td><?= e($u['role_name'] ?? $u['role_id']) ?></td>
                <td><?= $u['is_active'] ? '<span class="badge encours">actif</span>' : '<span class="badge annulee">désactivé</span>' ?></td>
                <td class="small"><?= e($u['last_login_at'] ?? '—') ?></td>
                <td><a class="btn btn-sm btn-outline" href="?r=/admin/users/<?= (int) $u['id'] ?>/edit">Éditer</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt"><?= $pagination->render('?r=/admin/users') ?></div>