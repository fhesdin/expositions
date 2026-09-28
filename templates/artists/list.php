<?php /** Artistes : annuaire */ ?>
<div class="page-head">
    <h1>Les artistes</h1>
    <p class="sub">Peintres, photographes, sculpteurs… exposés dans la Somme.</p>
    <?php if (\Core\Auth::check()): ?>
    <p><a class="btn btn-primary" href="?r=/artistes/create">➕ Créer une fiche artiste</a></p>
    <?php endif; ?>
</div>

<form method="get" class="filter-bar">
    <input type="hidden" name="r" value="/artistes">
    <div><label>Recherche</label><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="nom d'artiste…" data-suggest></div>
    <div><label>Commune d'atelier</label>
        <select name="commune"><option value="">Toutes</option>
        <?php foreach ($communes as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string)($filters['commune'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option><?php endforeach; ?>
        </select></div>
    <div class="filter-actions">
        <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
        <a class="btn btn-outline btn-sm" href="?r=/artistes">Réinitialiser</a>
    </div>
</form>

<?php if (empty($rows)): ?>
    <div class="empty">Aucun artiste pour l'instant.</div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($rows as $a): ?>
        <article class="card">
            <div class="body">
                <h3><a href="?r=/artistes/<?= e($a['slug']) ?>"><?= e($a['name']) ?></a></h3>
                <div class="meta">
                    <?php if (!empty($a['disciplines'])): ?><span><?= e($a['disciplines']) ?></span><?php endif; ?>
                    <?php if (!empty($a['commune_name'])): ?><span>📍 <?= e($a['commune_name']) ?></span><?php endif; ?>
                </div>
                <div class="badges">
                    <?php if (!empty($a['artist_verified'])): ?><span class="badge encours">✓ Page vérifiée</span><?php endif; ?>
                    <?php if ($a['workshop_open']): ?><span class="badge gratuit">Atelier ouvert au public</span><?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>