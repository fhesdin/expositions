<?php /** Lieux : annuaire filtrable */ ?>
<div class="page-head">
    <h1>Les lieux</h1>
    <p class="sub">Musées, galeries, médiathèques, châteaux… qui accueillent des expositions dans la Somme.</p>
</div>

<form method="get" class="filter-bar">
    <input type="hidden" name="r" value="/lieux">
    <div><label>Recherche</label><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="nom du lieu…" data-suggest></div>
    <div><label>Commune</label>
        <select name="commune"><option value="">Toutes</option>
        <?php foreach ($communes as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string)($filters['commune'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option><?php endforeach; ?>
        </select></div>
    <div><label>Type</label>
        <select name="type"><option value="">Tous</option>
        <?php foreach ($types as $t): ?><option value="<?= e($t['slug']) ?>" <?= ($filters['type'] ?? '') === $t['slug'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
        </select></div>
    <div class="filter-actions">
        <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
        <a class="btn btn-outline btn-sm" href="?r=/lieux">Réinitialiser</a>
        <?php if (\Core\Auth::check()): ?><a class="btn btn-outline btn-sm" href="?r=/lieux/create">+ Ajouter un lieu</a><?php endif; ?>
    </div>
</form>

<?php if (empty($rows)): ?>
    <div class="empty">Aucun lieu trouvé.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Nom</th><th>Type</th><th>Commune</th><th>Expos en cours</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><a href="?r=/lieux/<?= e($p['slug']) ?>"><b><?= e($p['name']) ?></b></a></td>
                    <td><?= e($p['type_name'] ?? '—') ?></td>
                    <td><?= e($p['commune_name'] ?? '—') ?></td>
                    <td><span class="badge encours"><?= (int) ($p['ongoing_count'] ?? 0) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt muted small"><?= count($rows) ?> lieu(x)</div>
<?php endif; ?>