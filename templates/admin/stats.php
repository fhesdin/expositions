<?php /** Admin : statistiques */ ?>
<div class="page-head"><h1>Statistiques</h1></div>

<div class="filter-bar">
    <?php foreach ($counters as $k => $v): ?>
        <span class="stat" style="background:var(--c-primary-soft);border-radius:10px;padding:.5rem .9rem"><?= e($k) ?> : <b><?= (int) $v ?></b></span>
    <?php endforeach; ?>
</div>

<div class="grid cols-3">
    <div class="card"><div class="body">
        <h3>Publications par mois (12 mois)</h3>
        <table class="list">
            <thead><tr><th>Mois</th><th>Expos</th></tr></thead>
            <tbody>
            <?php foreach ($byMonth as $r): ?>
                <tr><td><?= e($r['mois']) ?></td><td><b><?= (int) $r['n'] ?></b></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
    <div class="card"><div class="body">
        <h3>Par catégorie</h3>
        <table class="list">
            <thead><tr><th>Catégorie</th><th>Expos</th></tr></thead>
            <tbody>
            <?php foreach ($byCategory as $r): ?>
                <tr><td><span class="dot" style="display:inline-block;width:.8em;height:.8em;border-radius:50%;background:<?= e($r['color'] ?? '#999') ?>;margin-right:.3em"></span><?= e($r['name'] ?? 'Sans') ?></td><td><b><?= (int) $r['n'] ?></b></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
    <div class="card"><div class="body">
        <h3>Top communes</h3>
        <table class="list">
            <thead><tr><th>Commune</th><th>Expos</th></tr></thead>
            <tbody>
            <?php foreach ($byCommune as $r): ?>
                <tr><td><?= e($r['nom']) ?></td><td><b><?= (int) $r['n'] ?></b></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<section class="section">
    <h2>Modération (délai moyen de traitement)</h2>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Modérateur</th><th>Décisions</th><th>Délai moyen</th></tr></thead>
            <tbody>
            <?php foreach ($moderation as $m): ?>
                <tr><td><?= e($m['username']) ?></td><td><?= (int) $m['n'] ?></td><td><?= e($m['avg_h']) ?> h</td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>