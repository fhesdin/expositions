<?php /** Admin : référentiel communes */ ?>
<div class="page-head"><h1>Référentiel des communes</h1>
<p class="sub"><?= count($rows) ?> communes. Import du référentiel officiel possible via scripts/.</p></div>
<div class="table-wrap">
    <table class="list">
        <thead><tr><th>Code INSEE</th><th>Code postal</th><th>Commune</th><th>Lat</th><th>Lon</th><th>Expos</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
                <td class="small"><?= e($c['code_insee']) ?></td>
                <td class="small"><?= e($c['code_postal'] ?? '') ?></td>
                <td><b><?= e($c['nom']) ?></b></td>
                <td class="small"><?= e($c['latitude'] ?? '—') ?></td>
                <td class="small"><?= e($c['longitude'] ?? '—') ?></td>
                <td><?= (int) $c['n_expos'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>