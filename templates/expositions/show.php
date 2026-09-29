<?php
/** Fiche publique d'une exposition */

$tags = $tags ?? [];
$artists = $artists ?? [];
$img = $img ?? [];
$poster = $poster ?? null;
$isPerm = (int) ($expo['is_permanent'] ?? 0) === 1;
$today = date('Y-m-d');
$placeSlug = $expo['place_slug'] ?? null;
$placeUrl = $placeSlug ? '?r=/lieux/' . e($placeSlug) : null;
$visits = $visitCounts ?? ['going' => 0, 'went' => 0];
?>
<div class="page-head small"><a href="?r=/expositions">← Toutes les expositions</a></div>

<div class="detail-head">
    <div>
        <div class="detail-poster" style="<?= $poster ? "background-image:url('" . e($poster) . "')" : '' ?>">
            <?php if (!empty($expo['category_name'])): ?>
                <span class="cat-chip" style="position:relative;top:.6rem;left:.6rem;border-left-color:<?= e($expo['category_color'] ?? '#b4552d') ?>;display:inline-block"><?= e($expo['category_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h1><?= e($expo['title']) ?></h1>
            <?php if (!empty($structure) && !empty($structure['status']) && $structure['status'] === 'published'): ?>
            <p class="small">Organisée par <a href="?r=/structures/<?= e($structure['slug']) ?>"><b><?= e($structure['nom']) ?></b></a></p>
            <?php endif; ?>
            <p class="dates"><?= e(\App\Expositions\ExhibitionModel::datesHuman($expo)) ?></p>
            <p class="lieu">
                <?php if ($placeUrl): ?><a href="<?= $placeUrl ?>">📍 <?= e($expo['place_name'] ?? '') ?></a><?php else: ?>📍 <?= e($expo['place_name'] ?? 'Lieu à préciser') ?><?php endif; ?>
                <?php if (!empty($expo['commune_name'])): ?> — <?= e($expo['commune_name']) ?> (Somme)<?php endif; ?>
            </p>
            <?php if (!empty($disciplineNames)): ?>
            <p class="meta" style="margin:.25rem 0 0">🎨 <?= e(implode(' · ', $disciplineNames)) ?></p>
            <?php endif; ?>
            <div class="badges">
                <?php if ((int) ($expo['is_permanent'] ?? 0) === 1): ?><span class="badge encours">Permanente</span>
                <?php elseif (($expo['status'] ?? '') === 'archived'): ?><span class="badge terminee">Archivée</span>
                <?php else: ?>
                    <?php if (($expo['price_kind'] ?? '') === 'gratuit'): ?><span class="badge gratuit">Gratuit</span><?php endif; ?>
                    <?php if (($expo['price_kind'] ?? '') === 'gratuit' && ($expo['price_detail'] ?? null)): ?><span class="muted small"><?= e($expo['price_detail']) ?></span><?php endif; ?>
                    <?php if ($expo['access_pmr']): ?><span class="badge">♿ PMR</span><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside class="side-card">
        <?php if (\Core\Auth::check()): ?>
            <form method="post" action="?r=/favoris/toggle">
                <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                <input type="hidden" name="kind" value="exhibition">
                <input type="hidden" name="item_id" value="<?= (int) $expo['id'] ?>">
                <button class="btn btn-outline btn-block <?= $isFavorite ? '' : 'btn-primary' ?>" type="submit" data-ajax-toggle>
                    <?= $isFavorite ? '★ Dans mes favoris' : '☆ Ajouter à mes favoris' ?>
                </button>
            </form>
            <form method="post" action="?r=/visites/toggle">
                <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                <input type="hidden" name="exhibition_id" value="<?= (int) $expo['id'] ?>">
                <?php $myGoing = (int) ($myVisit['going'] ?? 0) === 1; $myWent = (int) ($myVisit['went'] ?? 0) === 1; ?>
                <button class="btn btn-outline btn-block <?= $myGoing ? 'btn-primary' : '' ?>" name="which" value="going" type="submit" data-ajax-toggle>
                    🙋 J'y vais (<?= (int) $visits['going'] ?>)
                </button>
                <?php if (!empty($expo['ends_at']) && $expo['ends_at'] < $today || $isPerm): ?>
                <button class="btn btn-outline btn-block <?= $myWent ? 'btn-primary' : '' ?>" name="which" value="went" type="submit" data-ajax-toggle>
                    ✅ J'y suis allé(e) (<?= (int) $visits['went'] ?>)
                </button>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <a class="btn btn-outline btn-block" href="?r=/login">☆ Connectez-vous pour suivre cette expo</a>
        <?php endif; ?>

        <a class="btn btn-primary btn-block" href="?r=/ical/exposition/<?= e($expo['slug']) ?>">📅 Ajouter à mon agenda (.ics)</a>
        <button class="btn btn-outline btn-block" type="button" data-copy-link>🔗 Copier le lien</button>

        <?php if (!empty($expo['website'])): ?>
            <a class="btn btn-outline btn-block" href="<?= e($expo['website']) ?>" target="_blank" rel="noopener">🌐 Site officiel</a>
        <?php endif; ?>

        <details class="mt">
            <summary class="small muted">Signaler un problème</summary>
            <form method="post" action="?r=/signaler" class="mt">
                <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                <input type="hidden" name="kind" value="exhibition">
                <input type="hidden" name="item_id" value="<?= (int) $expo['id'] ?>">
                <div class="field">
                    <textarea name="reason" required placeholder="Que faut-il corriger ? (dates, horaires, erreur…)" style="min-height:70px"></textarea>
                </div>
                <button class="btn btn-sm btn-outline" type="submit">Envoyer</button>
            </form>
        </details>

        <?php if (\Core\Auth::check() && ((int) ($expo['submitted_by'] ?? 0) === \Core\Auth::id() || \Core\Auth::isModerator())): ?>
            <hr style="border-color:var(--c-line)">
            <a class="btn btn-outline btn-block" href="?r=/expositions/<?= (int) $expo['id'] ?>/edit">✏️ Modifier la fiche</a>
        <?php endif; ?>
    </aside>
</div>

<?php if (!empty($expo['summary'])): ?>
<section class="section"><h2>L'exposition</h2><p><?= nl2br(e($expo['summary'])) ?></p></section>
<?php endif; ?>

<?php if ($tags !== []): ?>
<section class="section">
    <h2>Tags</h2>
    <div>
        <?php foreach ($tags as $t): ?>
            <span class="tag-chip"><a href="?r=/expositions&amp;tag=<?= e($t['slug']) ?>">#<?= e($t['name']) ?></a></span>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($artists !== []): ?>
<section class="section">
    <h2>Artistes exposé(e)s</h2>
    <div class="chips">
        <?php foreach ($artists as $a): ?>
            <?php if (!empty($a['slug'])): ?>
                <a class="artist-chip" href="?r=/artistes/<?= e($a['slug']) ?>"><?= e($a['name'] ?: $a['free_name']) ?></a>
            <?php else: ?>
                <span class="artist-chip"><?= e($a['free_name'] ?: $a['name']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php $pub = $pub ?? []; ?>
<?php if ($pub !== []): ?>
<section class="section">
    <h2>Infos pratiques</h2>
    <div class="kv">
        <?php foreach ($pub as $row): ?>
            <div class="row"><span class="k"><?= e($row['label']) ?></span><span><?= e($row['value']) ?></span></div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($img !== []): ?>
<section class="section">
    <h2>Photos</h2>
    <div class="gallery">
        <?php foreach ($img as $im): ?>
            <figure><img src="<?= e($im['path']) ?>" alt="<?= e($im['caption'] ?? '') ?>" loading="lazy">
            <?php if (!empty($im['caption'])): ?><figcaption><?= e($im['caption']) ?></figcaption><?php endif; ?></figure>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($events)): ?>
<section class="section">
    <h2>Événements (vernissages, visites guidées…)</h2>
    <?php foreach ($events as $ev): ?>
        <div class="event-item">
            <span class="when"><?= e(date_fr($ev['starts_at'], 'd/m/Y \à H:i')) ?></span>
            <div>
                <b><?= e($ev['title']) ?></b>
                <?php if (!empty($ev['description'])): ?><p class="muted small" style="margin:.2rem 0"><?= e($ev['description']) ?></p><?php endif; ?>
                <?php if ($ev['requires_reservation']): ?><span class="badge">Sur réservation</span><?php endif; ?>
                <?php if (\Core\Auth::check() && ((int) ($expo['submitted_by'] ?? 0) === \Core\Auth::id() || \Core\Auth::isModerator())): ?>
                    <form method="post" action="?r=/expositions/events/<?= (int) $ev['id'] ?>/delete" data-confirm="Supprimer cet événement ?">
                        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                        <button class="btn btn-sm btn-danger" type="submit">Supprimer</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (!empty($samePlace) || !empty($sameArtists)): ?>
<section class="section">
    <h2>Autour de cette exposition</h2>
    <div class="grid cols-3">
        <?php foreach ($samePlace as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?>
        <?php foreach ($sameArtists as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?>
    </div>
</section>
<?php endif; ?>