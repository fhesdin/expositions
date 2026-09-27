<?php /** Page publique d'un lieu */ 
$canManage = \Core\Auth::check() && (\Core\Auth::isModerator() || \App\Places\PlaceModel::isManager((int) $place['id'], \Core\Auth::id()));
?>
<div class="page-head small"><a href="?r=/lieux">← Tous les lieux</a></div>
<div class="page-head">
    <h1><?= e($place['name']) ?></h1>
    <p class="sub">
        <?= e($place['type_name'] ?? '') ?> · <?= e($place['address'] ?? '') ?>, <?= e($place['commune_name'] ?? '') ?>
        <?php if ($place['access_pmr']): ?><span class="badge">♿ PMR</span><?php endif; ?>
    </p>
</div>

<?php if (!empty($place['description'])): ?>
<section class="section"><p><?= nl2br(e($place['description'])) ?></p></section>
<?php endif; ?>

<div class="kv">
    <?php if (!empty($place['opening_hours'])): ?><div class="row"><span class="k">Horaires</span><span><?= e($place['opening_hours']) ?></span></div><?php endif; ?>
    <?php if (!empty($place['access_info'])): ?><div class="row"><span class="k">Fermeture</span><span><?= e($place['access_info']) ?></span></div><?php endif; ?>
    <?php if (!empty($place['phone'])): ?><div class="row"><span class="k">Téléphone</span><span><?= e($place['phone']) ?></span></div><?php endif; ?>
    <?php if (!empty($place['email'])): ?><div class="row"><span class="k">E-mail</span><span><?= e($place['email']) ?></span></div><?php endif; ?>
    <?php if (!empty($place['website'])): ?><div class="row"><span class="k">Site</span><span><a href="<?= e($place['website']) ?>" target="_blank" rel="noopener"><?= e($place['website']) ?></a></span></div><?php endif; ?>
    <?php if ($place['latitude'] !== null): ?><div class="row"><span class="k">Coordonnées</span><span><?= e(coord((float) $place['latitude'])) ?>, <?= e(coord((float) $place['longitude'])) ?></span></div><?php endif; ?>
</div>

<?php if ($canManage): ?>
<div class="flex mb"><a class="btn btn-outline" href="?r=/lieux/<?= (int) $place['id'] ?>/edit">✏️ Modifier ce lieu</a></div>
<?php elseif (\Core\Auth::check()): ?>
<div class="flex mb">
    <form method="post" action="?r=/lieux/<?= (int) $place['id'] ?>/claim">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <button class="btn btn-outline btn-sm" type="submit" data-confirm="Demander à gérer ce lieu (vous devez y être habilité) ?">Je gère ce lieu — demander la gestion</button>
    </form>
</div>
<?php endif; ?>

<section class="section">
    <h2>Expositions en cours</h2>
    <?php if (empty($ongoing)): ?><div class="empty">Aucune exposition en cours actuellement.</div>
    <?php else: ?><div class="grid cols-3"><?php foreach ($ongoing as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?></div><?php endif; ?>
</section>

<?php if (!empty($upcoming)): ?>
<section class="section">
    <h2>À venir</h2>
    <div class="grid cols-3"><?php foreach ($upcoming as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?></div>
</section>
<?php endif; ?>

<?php if (!empty($past)): ?>
<section class="section">
    <h2>Archives</h2>
    <div class="grid cols-3"><?php foreach ($past as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?></div>
</section>
<?php endif; ?>