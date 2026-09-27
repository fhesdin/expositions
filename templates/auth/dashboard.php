<?php /** Mon espace : tableau de bord contributeur */ ?>
<div class="page-head">
    <h1>Mon espace</h1>
    <p class="sub">Vos fiches, lieux gérés, agenda et favoris.</p>
</div>

<div class="flex mb">
    <a class="btn btn-primary" href="?r=/expositions/create">+ Nouvelle exposition</a>
    <?php if ($artistProfile === null): ?><a class="btn btn-outline" href="?r=/artistes/create">Créer ma page artiste</a><?php endif; ?>
    <a class="btn btn-outline" href="?r=/lieux/create">Ajouter un lieu</a>
</div>

<section class="section">
    <h2>Mes fiches à traiter <span class="muted small">(brouillons, en attente, refusées)</span></h2>
    <?php if (empty($expos)): ?>
        <div class="empty">Aucune fiche à traiter. Vos fiches publiées apparaissent directement sur le site.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Titre</th><th>Statut</th><th>Dates</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($expos as $e2): ?>
                <tr>
                    <td><b><?= e($e2['title']) ?></b></td>
                    <td><span class="badge <?= $e2['status'] === 'rejected' ? 'annulee' : 'nouveau' ?>"><?= e($e2['status']) ?></span>
                        <?php if (!empty($e2['reject_reason'])): ?><div class="small muted">Motif : <?= e($e2['reject_reason']) ?></div><?php endif; ?></td>
                    <td class="small"><?= e($e2['starts_at'] ?? '?') ?> → <?= e($e2['ends_at'] ?? '?') ?></td>
                    <td><a class="btn btn-sm btn-outline" href="?r=/expositions/<?= (int) $e2['id'] ?>/edit">Modifier</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php if ($managedPlaces !== []): ?>
<section class="section">
    <h2>Lieux que je gère</h2>
    <ul>
        <?php foreach ($managedPlaces as $p): ?>
            <li><a href="?r=/lieux/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a> <a class="small" href="?r=/lieux/<?= (int) $p['id'] ?>/edit">(modifier)</a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($artistProfile !== null): ?>
<section class="section">
    <h2>Ma page artiste</h2>
    <p><a href="?r=/artistes/<?= e($artistProfile['slug']) ?>"><?= e($artistProfile['name']) ?></a>
       <span class="badge <?= $artistProfile['status'] === 'published' ? 'encours' : 'nouveau' ?>"><?= e($artistProfile['status']) ?></span>
       <a class="small" href="?r=/artistes/<?= (int) $artistProfile['id'] ?>/edit">(modifier)</a></p>
</section>
<?php endif; ?>

<section class="section">
    <h2>Mon agenda <span class="muted small">(expositions suivies)</span></h2>
    <?php if (empty($agenda)): ?><div class="empty">Aucune expo suivie. Ajoutez-en depuis les fiches ☆ ou « J'y vais ».</div>
    <?php else: ?>
    <div class="grid cols-3"><?php foreach ($agenda as $e2): View::partial('partials/_expo_card', ['expo' => $e2]); endforeach; ?></div>
    <p class="small mt">Flux iCal : <a href="<?= e($icalUrl) ?>">s'abonner depuis un logiciel d'agenda</a></p>
    <?php endif; ?>
</section>

<?php if ($favorites !== []): ?>
<section class="section">
    <h2>Mes favoris</h2>
    <ul>
        <?php foreach ($favorites as $f): ?>
            <li>[<?= e($f['kind']) ?>] <?php if ($f['slug']): ?><a href="?r=/<?= e($f['kind'] === 'exhibition' ? 'expositions' : ($f['kind'] === 'artist' ? 'artistes' : 'lieux')) ?>/<?= e($f['slug']) ?>"><?= e($f['title']) ?></a><?php else: ?><?= e($f['title']) ?><?php endif; ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>