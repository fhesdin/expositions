<?php /** Fiche publique structure */ ?>
<div class="page-head">
    <h1><?= e($s['nom']) ?><?= $s['status'] !== 'published' ? ' <span class="badge">en attente</span>' : '' ?></h1>
    <p class="sub"><?= e(\App\Models\StructureModel::TYPES[$s['type']] ?? $s['type']) ?>
        <?= $s['commune_name'] ? ' · ' . e($s['commune_name']) : '' ?></p>
</div>

<div class="form-card">
    <p><?= nl2br(e($s['description'] ?: 'Pas encore de description.')) ?></p>
    <?php if ($s['website']): ?><p>🌐 <a href="<?= e($s['website']) ?>" rel="noopener" target="_blank"><?= e($s['website']) ?></a></p><?php endif; ?>
    <?php if ($s['email']): ?><p>✉️ <a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></p><?php endif; ?>
    <?php if ($s['phone']): ?><p>📞 <?= e($s['phone']) ?></p><?php endif; ?>
</div>

<?php if ($places): ?>
<section class="mod-section">
    <h2>Lieux gérés (<?= count($places) ?>)</h2>
    <ul class="list">
        <?php foreach ($places as $p): ?>
        <li><a href="?r=/lieux/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a> <span class="muted small"><?= e($p['type_name'] ?: '') ?></span></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($expos): ?>
<section class="mod-section">
    <h2>Expositions (<?= count($expos) ?>)</h2>
    <ul class="list">
        <?php foreach ($expos as $x): ?>
        <li>
            <a href="?r=/expositions/<?= e($x['slug']) ?>"><?= e($x['title']) ?></a>
            <span class="muted small">
                <?= !empty($x['is_permanent']) ? 'permanente' : e(\App\Expositions\ExhibitionModel::datesHuman($x)) ?>
                <?= !empty($x['ongoing']) ? ' · en cours' : '' ?>
            </span>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<div class="form-card">
    <?php if ($myRole === null && Auth::id()): ?>
        <form method="post" action="?r=/structures/join">
            <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
            <input type="hidden" name="structure_id" value="<?= (int) $s['id'] ?>">
            <button class="btn btn-primary" type="submit">Demander à rejoindre cette structure</button>
        </form>
    <?php elseif ($myRole === null): ?>
        <p class="muted small"><a href="?r=/login">Connectez-vous</a> pour rejoindre cette structure.</p>
    <?php endif; ?>
    <?php if ($myRole): ?>
        <p class="small">Votre rôle dans cette structure : <b><?= e($myRole) ?></b></p>
        <?php if (in_array($myRole, ['admin'], true) || Auth::minRank(5)): ?>
            <p><a class="btn btn-outline btn-sm" href="?r=/structures/<?= (int) $s['id'] ?>/members">Gérer les membres</a>
            <a class="btn btn-outline btn-sm" href="?r=/structures/<?= (int) $s['id'] ?>/edit">Modifier</a></p>
        <?php endif; ?>
        <?php if (in_array($myRole, ['admin', 'contributor'], true)): ?>
                    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!empty($s['created_by']) && \Core\Auth::id() === (int) $s['created_by'] && $s['status'] === 'pending'): ?>
<p class="muted small">Votre structure est en attente de validation par l'équipe de modération.</p>
<?php endif; ?>
