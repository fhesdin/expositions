<?php /** Annuaire des structures */ ?>
<div class="page-head"><h1>Structures</h1>
<p class="sub">Associations, musées, collectifs et services culturels de la Somme.</p></div>

<form method="get" action="?r=/structures" class="filter-bar">
    <input type="hidden" name="r" value="/structures">
    <div><label>Type</label>
        <select name="type">
            <option value="">Tous</option>
            <?php foreach ($types as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $currentType === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select></div>
    <div><label>Recherche</label><input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom…"></div>
    <button class="btn btn-primary" type="submit">Filtrer</button>
</form>

<?php if (Auth::minRank(3)): ?>
<p style="margin:.5rem 0"><a class="btn btn-outline" href="?r=/structures/create">+ Créer une structure</a></p>
<?php endif; ?>

<div class="cards-grid">
    <?php foreach ($rows as $s): ?>
    <a class="card structure-card" href="?r=/structures/<?= e($s['slug']) ?>">
        <h3><?= e($s['nom']) ?></h3>
        <p class="muted small"><?= e($types[$s['type']] ?? $s['type']) ?><?= $s['commune_name'] ? ' · ' . e($s['commune_name']) : '' ?></p>
        <p class="small"><?= (int) $s['places_count'] ?> lieu(x) · <?= (int) $s['expos_count'] ?> exposition(s)</p>
    </a>
    <?php endforeach; ?>
</div>
<?php if (!$rows): ?>
<div class="empty">Aucune structure.<?= Auth::minRank(3) ? ' <a href="?r=/structures/create">Créez la première</a>.' : '' ?></div>
<?php endif; ?>
