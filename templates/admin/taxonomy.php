<?php /** Admin : taxonomie (catégories, tags, types de lieux, publics) */ ?>
<div class="page-head"><h1>Taxonomie</h1></div>

<section class="section">
    <h2>Catégories</h2>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Nom</th><th>Couleur</th><th>Active</th><th>Enregistrer</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <form method="post" action="?r=/admin/taxonomy/categories/<?= (int) $c['id'] ?>">
                    <td><input type="text" name="name" value="<?= e($c['name']) ?>"></td>
                    <td><input type="color" name="color" value="<?= e($c['color']) ?>"></td>
                    <td><input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?>></td>
                    <td>
                        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                        <button class="btn btn-sm btn-outline" type="submit">OK</button>
                    </td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="?r=/admin/taxonomy/categories" class="filter-bar mt">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <div><label>Nouvelle catégorie</label><input type="text" name="name" required></div>
        <div><label>Couleur</label><input type="color" name="color" value="#b4552d"></div>
        <div><label>Icône (emoji)</label><input type="text" name="icon" maxlength="8"></div>
        <button class="btn btn-primary btn-sm" type="submit">Ajouter</button>
    </form>
</section>

<section class="section">
    <h2>Tags (<?= count($tags) ?>)</h2>
    <form method="post" action="?r=/admin/taxonomy/tags/create" class="filter-bar">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <div><label>Nouveau tag</label><input type="text" name="name" maxlength="80" required></div>
        <button class="btn btn-primary btn-sm" type="submit">Ajouter</button>
    </form>
    <form method="post" action="?r=/admin/taxonomy/tags/merge" class="filter-bar">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <div><label>Fusionner le tag (id)</label><input type="number" name="from_id" required></div>
        <div><label>dans (id)</label><input type="number" name="into_id" required></div>
        <button class="btn btn-primary btn-sm" type="submit" data-confirm="Fusionner ces tags ?">Fusionner</button>
    </form>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Tag</th><th>Utilisations</th><th>État</th><th></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($tags, 0, 100) as $t): ?>
                <tr>
                    <td><?= e($t['name']) ?></td>
                    <td><?= (int) $t['used'] ?></td>
                    <td><?= $t['is_approved'] ? '<span class="badge encours">approuvé</span>' : '<span class="badge nouveau">à valider</span>' ?></td>
                    <td class="actions">
                        <?php if (!$t['is_approved']): ?>
                        <form method="post" action="?r=/admin/taxonomy/tags/<?= (int) $t['id'] ?>/approve">
                            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                            <button class="btn btn-sm btn-primary" type="submit">Approuver</button>
                        </form>
                        <?php endif; ?>
                        <details>
                            <summary class="btn btn-sm btn-outline">Renommer</summary>
                            <form method="post" action="?r=/admin/taxonomy/tags/<?= (int) $t['id'] ?>/edit" class="filter-bar">
                                <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                                <input type="text" name="name" value="<?= e($t['name']) ?>" maxlength="80" required>
                                <button class="btn btn-sm btn-primary" type="submit">Enregistrer</button>
                            </form>
                        </details>
                        <form method="post" action="?r=/admin/taxonomy/tags/<?= (int) $t['id'] ?>/delete" data-confirm="Supprimer ce tag ? Les liaisons seront perdues.">
                            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <h2>Types de lieux &amp; publics</h2>
    <div class="grid cols-2">
        <div class="card"><div class="body">
            <h3>Types de lieux</h3>
            <?php foreach ($placeTypes as $t): ?><span class="tag-chip"><?= e($t['name']) ?></span><?php endforeach; ?>
        </div></div>
        <div class="card"><div class="body">
            <h3>Publics</h3>
            <?php foreach ($publics as $p): ?><span class="tag-chip"><?= e($p['label']) ?></span><?php endforeach; ?>
        </div></div>
    </div>
</section>