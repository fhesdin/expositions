<?php
/** Accueil public */
?>
<section class="hero">
    <div class="inner">
        <h1>Toutes les expositions de la Somme, au même endroit.</h1>
        <p>Musées, galeries, médiathèques, ateliers d'artistes : trouvez une expo par date, par lieu ou par thème — près de chez vous. Gratuit pour le public comme pour les lieux.</p>
        <form method="get" action="?r=/expositions" class="search-bar" role="search">
            <input type="hidden" name="r" value="/expositions">
            <input type="search" name="q" value="" placeholder="Une expo, un artiste, une commune…" data-suggest aria-label="Rechercher une exposition">
            <button type="submit">Rechercher</button>
        </form>
        <div class="stat-strip">
            <span class="stat"><b><?= (int) $counters['ongoing'] ?></b> expo(s) en cours</span>
            <span class="stat"><b><?= (int) $counters['places'] ?></b> lieux</span>
            <span class="stat"><b><?= (int) $counters['structures'] ?></b> structures</span>
            <span class="stat"><b><?= (int) $counters['artists'] ?></b> artistes</span>
            <span class="stat"><b><?= (int) $counters['communes'] ?></b> communes</span>
        </div>
    </div>
</section>

<?php if (!empty($disciplines)): ?>
<section class="section">
    <h2>🎨 Explorer par discipline</h2>
    <div class="chip-cloud" style="display:flex;flex-wrap:wrap;gap:.5rem">
        <?php foreach ($disciplines as $d): ?>
        <a class="tag-chip" href="?r=/artistes&amp;discipline=<?= (int) $d['id'] ?>">
            <?= e($d['name']) ?> <b><?= (int) $d['n'] ?></b>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($featured)): ?>
<section class="section">
    <h2>⭐ À la une <a class="more" href="?r=/expositions">tout voir →</a></h2>
    <div class="grid cols-3">
        <?php foreach ($featured as $e): View::partial('partials/_expo_card', ['expo' => $e]); endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <h2>🎉 Ce week-end <a class="more" href="?r=/expositions&amp;period=week-end">tout voir →</a></h2>
    <?php if (empty($weekend)): ?>
        <div class="empty">Aucune exposition recensée pour ce week-end (pour l'instant !).</div>
    <?php else: ?>
    <div class="grid cols-3"><?php foreach ($weekend as $e): View::partial('partials/_expo_card', ['expo' => $e]); endforeach; ?></div>
    <?php endif; ?>
</section>

<?php if (!empty($vernissages)): ?>
<section class="section">
    <h2>🥂 Vernissages de la semaine</h2>
    <div class="grid cols-3">
        <?php foreach ($vernissages as $ev): ?>
        <article class="card">
            <div class="body">
                <h3><a href="?r=/expositions/<?= e($ev['exhibition_slug']) ?>"><?= e($ev['title']) ?></a></h3>
                <div class="meta"><span><?= e(date_fr($ev['starts_at'], 'd/m/Y H:i')) ?></span>
                    <?php if (!empty($ev['place_name'])): ?><span>📍 <?= e($ev['place_name']) ?></span><?php endif; ?></div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($lastChance)): ?>
<section class="section">
    <h2>⏳ Dernières chances <span class="muted small">(se termine sous 10 jours)</span></h2>
    <div class="grid cols-3"><?php foreach ($lastChance as $e): View::partial('partials/_expo_card', ['expo' => $e]); endforeach; ?></div>
</section>
<?php endif; ?>

<?php if (!empty($newest)): ?>
<section class="section">
    <h2>✨ Nouveautés <a class="more" href="?r=/expositions&amp;sort=nouveautes">tout voir →</a></h2>
    <div class="grid cols-3"><?php foreach ($newest as $e): View::partial('partials/_expo_card', ['expo' => $e]); endforeach; ?></div>
</section>
<?php endif; ?>

<section class="section">
    <h2>Explorer par catégorie</h2>
    <div class="flex">
        <?php foreach ($categories as $c): ?>
            <a class="tag-chip" style="border-left:4px solid <?= e($c['color']) ?>" href="?r=/expositions&amp;category=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <h2>Trois façons de chercher</h2>
    <div class="grid cols-3">
        <div class="card"><div class="body"><h3>📋 Liste</h3><p class="muted small">Filtres combinables : dates, commune, catégorie, gratuit, PMR…</p><a class="btn btn-primary" href="?r=/expositions">Voir la liste</a></div></div>
        <div class="card"><div class="body"><h3>🗓️ Calendrier</h3><p class="muted small">Vue mois : périodes et vernissages, export iCal.</p><a class="btn btn-primary" href="?r=/calendrier">Voir le calendrier</a></div></div>
        <div class="card"><div class="body"><h3>🗺️ Carte</h3><p class="muted small">Autour de vous, OpenStreetMap, marqueurs par lieu.</p><a class="btn btn-primary" href="?r=/carte">Voir la carte</a></div></div>
    </div>
</section>