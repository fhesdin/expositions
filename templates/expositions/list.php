<?php /** Liste des expositions + filtres combinables (state en URL) */ ?>
<div class="page-head">
    <h1>Les expositions</h1>
    <p class="sub"><?= count($rows) ?> résultat(s) sur cette page · <a href="?r=/expositions%2Fpropose">une expo manque ? proposez-la</a></p>
</div>

<form method="get" class="filter-bar">
    <input type="hidden" name="r" value="/expositions">
    <div><label>Recherche</label><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="titre, artiste, lieu…" data-suggest></div>
    <div><label>Catégorie</label>
        <select name="category"><option value="">Toutes</option>
        <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>" <?= ($filters['category'] ?? '') === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
    <div><label>Commune</label>
        <select name="commune"><option value="">Toutes</option>
        <?php foreach ($communes as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string)($filters['commune'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option><?php endforeach; ?>
        </select></div>
    <div><label>Type de lieu</label>
        <select name="type"><option value="">Tous</option>
        <?php foreach ($placeTypes as $t): ?><option value="<?= e($t['slug']) ?>" <?= ($filters['type'] ?? '') === $t['slug'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
        </select></div>
    <div><label>Période</label>
        <select name="period">
            <option value="">Toutes les dates</option>
            <option value="aujourdhui" <?= ($filters['period'] ?? '') === 'aujourdhui' ? 'selected' : '' ?>>Aujourd'hui</option>
            <option value="week-end" <?= ($filters['period'] ?? '') === 'week-end' ? 'selected' : '' ?>>Ce week-end</option>
            <option value="semaine" <?= ($filters['period'] ?? '') === 'semaine' ? 'selected' : '' ?>>Cette semaine</option>
            <option value="mois" <?= ($filters['period'] ?? '') === 'mois' ? 'selected' : '' ?>>Ce mois-ci</option>
        </select></div>
    <div><label>Gratuit</label><input type="checkbox" name="free" value="1" <?= !empty($filters['free']) ? 'checked' : '' ?>></div>
    <div><label>Accessible PMR</label><input type="checkbox" name="pmr" value="1" <?= !empty($filters['pmr']) ? 'checked' : '' ?>></div>
    <div><label>Archives</label>
        <select name="scope">
            <option value="active" <?= ($filters['scope'] ?? 'active') === 'active' ? 'selected' : '' ?>>Exclure les archives</option>
            <option value="archives" <?= ($filters['scope'] ?? '') === 'archives' ? 'selected' : '' ?>>Archives seules</option>
            <option value="all" <?= ($filters['scope'] ?? '') === 'all' ? 'selected' : '' ?>>Tout</option>
        </select></div>
    <div><label>Tri</label>
        <select name="sort">
            <option value="pertinence" <?= ($filters['sort'] ?? '') === 'pertinence' ? 'selected' : '' ?>>Pertinence</option>
            <option value="debut" <?= ($filters['sort'] ?? '') === 'debut' ? 'selected' : '' ?>>Date de début</option>
            <option value="fin" <?= ($filters['sort'] ?? '') === 'fin' ? 'selected' : '' ?>>Fin proche</option>
            <option value="nouveautes" <?= ($filters['sort'] ?? '') === 'nouveautes' ? 'selected' : '' ?>>Nouveautés</option>
        </select></div>
    <div class="filter-actions">
        <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
        <a class="btn btn-outline btn-sm" href="?r=/expositions">Réinitialiser</a>
    </div>
</form>

<?php if (empty($rows)): ?>
    <div class="empty">Aucune exposition ne correspond à ces critères.<br><a href="?r=/expositions%2Fpropose">Proposer une exposition manquante →</a></div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($rows as $e): View::partial('partials/_expo_card', ['expo' => $e]); endforeach; ?>
    </div>
    <div class="mt"><?= $pagination->render('?r=/expositions&' . http_build_query(array_filter($filters, fn($v) => $v !== '' && $v !== null))) ?></div>
<?php endif; ?>