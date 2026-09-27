<?php /** Admin : liste utilisateurs */ ?>
<div class="page-head"><h1>Utilisateurs</h1></div>
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