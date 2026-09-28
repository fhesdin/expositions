<?php /** Page publique artiste */
$canEdit = \Core\Auth::check() && (\Core\Auth::isModerator() || (int) ($artist['user_id'] ?? 0) === \Core\Auth::id());
?>
<div class="page-head small"><a href="?r=/artistes">← Tous les artistes</a></div>
<div class="page-head">
    <h1><?= e($artist['name']) ?></h1>
    <p class="sub">
        <?= e(implode(', ', $disciplineNames ?? [])) ?>
        <?php if (!empty($artist['commune_name'])): ?> · 📍 <?= e($artist['commune_name']) ?><?php endif; ?>
        <?php if ($artist['artist_verified']): ?><span class="badge encours">✓ Page vérifiée</span><?php endif; ?>
        <?php if ($artist['workshop_open']): ?><span class="badge gratuit">Atelier ouvert au public</span><?php endif; ?>
    </p>
</div>

<?php if (!empty($artist['bio'])): ?>
<section class="section"><h2>Biographie</h2><p><?= nl2br(e($artist['bio'])) ?></p></section>
<?php endif; ?>

<?php if (!empty($artist['statement'])): ?>
<section class="section"><h2>Démarche</h2><p><?= nl2br(e($artist['statement'])) ?></p></section>
<?php endif; ?>

<?php if (!empty($artist['workshop_info']) && $artist['workshop_open']): ?>
<section class="section"><h2>Atelier</h2><p><?= nl2br(e($artist['workshop_info'])) ?></p></section>
<?php endif; ?>

<?php if (!empty($artist['website'])): ?>
<p><a class="btn btn-outline" href="<?= e($artist['website']) ?>" target="_blank" rel="noopener">🌐 Site / portfolio</a></p>
<?php endif; ?>

<?php if ($works !== []): ?>
<section class="section">
    <h2>Œuvres</h2>
    <div class="gallery">
        <?php foreach ($works as $w): ?>
            <figure><img src="<?= e($w['image_path']) ?>" alt="<?= e($w['title'] ?? '') ?>" loading="lazy">
            <?php if (!empty($w['title'])): ?><figcaption><?= e($w['title']) ?><?= $w['year'] ? ' (' . e($w['year']) . ')' : '' ?></figcaption><?php endif; ?></figure>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (\Core\Auth::check() && !$canEdit && $artist['user_id'] === null): ?>
    <form method="post" action="?r=/artistes/<?= (int) $artist['id'] ?>/claim" class="mb">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <div class="field"><input type="text" name="justification" placeholder="Justifiez votre demande (vous êtes cet artiste)…"></div>
        <button class="btn btn-outline btn-sm" type="submit" data-confirm="Envoyer une demande de revendication de cette page ?">Je suis cet(te) artiste — revendiquer la page</button>
    </form>
<?php endif; ?>

<?php if ($canEdit): ?>
    <a class="btn btn-outline mb" href="?r=/artistes/<?= (int) $artist['id'] ?>/edit">✏️ Modifier ma page</a>
<?php endif; ?>

<?php foreach (['ongoing' => 'Expositions en cours', 'upcoming' => 'À venir', 'archived' => 'Archives'] as $scope => $label): ?>
    <?php if (!empty($expos[$scope])): ?>
    <section class="section">
        <h2><?= e($label) ?></h2>
        <div class="grid cols-3"><?php foreach ($expos[$scope] as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?></div>
    </section>
    <?php endif; ?>
<?php endforeach; ?>