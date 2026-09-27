<?php /** File d'attente de modération */
$mc = fn(array $a) => '<form method="post" class="mod-actions" action="' . e($a['action']) . '">'
    . '<input type="hidden" name="_csrf" value="' . e(\Core\Csrf::token()) . '">'
    . implode('', array_map(fn($h) => (string) $h, $a['hidden'] ?? []))
    . '<button class="btn btn-sm ' . e($a['class']) . '" type="submit">' . e($a['label']) . '</button></form>';
?>
<div class="page-head">
    <h1>Modération</h1>
    <p class="sub">File d'attente et journal des actions.</p>
</div>

<div class="filter-bar">
    <?php foreach ($counts as $k => $v): ?>
        <span class="badge <?= $v > 0 ? 'dernier' : '' ?>"><?= e($k) ?> : <b><?= (int) $v ?></b></span>
    <?php endforeach; ?>
</div>

<section class="mod-section">
    <h2>🖼️ Expositions en attente (<?= count($exhibitions) ?>)</h2>
    <?php if (empty($exhibitions)): ?><p class="muted small">Rien à modérer. 🎉</p><?php endif; ?>
    <?php foreach ($exhibitions as $x): ?>
        <div class="mod-card">
            <div class="title"><?= e($x['title']) ?> <span class="muted small">— <?= e($x['place_name'] ?? $x['place_free_text'] ?? 'lieu ?') ?>, <?= e($x['commune_name'] ?? '?') ?></span></div>
            <div class="meta">proposée par <?= e($x['submitter'] ?? $x['source_name'] ?? 'anonyme') ?> · <?= e($x['created_at']) ?></div>
            <p class="small" style="margin:.4rem 0"><?= e(excerpt($x['summary'] ?? '', 200)) ?></p>
            <div class="mod-actions">
                <a class="btn btn-sm btn-outline" href="?r=/expositions/<?= (int) $x['id'] ?>/edit">Vérifier / corriger</a>
                <?= $mc(['action' => '?r=/moderation/expositions/' . (int) $x['id'] . '/approve', 'label' => '✓ Valider', 'class' => 'btn-primary']) ?>
                <form method="post" class="mod-actions" action="?r=/moderation/expositions/<?= (int) $x['id'] ?>/reject">
                    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                    <input type="text" name="reason" placeholder="motif du refus (obligatoire)">
                    <button class="btn btn-sm btn-danger" type="submit">✗ Refuser</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="mod-section">
    <h2>🏛️ Lieux en attente (<?= count($places) ?>)</h2>
    <?php foreach ($places as $p): ?>
        <div class="mod-card">
            <div class="title"><?= e($p['name']) ?> <span class="muted small">— <?= e($p['commune_name'] ?? '?') ?></span></div>
            <div class="meta">proposé par <?= e($p['submitter'] ?? '?') ?> · <?= e($p['created_at']) ?></div>
            <div class="mod-actions">
                <?= $mc(['action' => '?r=/moderation/lieux/' . (int) $p['id'] . '/approve', 'label' => '✓ Valider', 'class' => 'btn-primary']) ?>
                <?= $mc(['action' => '?r=/moderation/lieux/' . (int) $p['id'] . '/reject', 'label' => '✗ Refuser', 'class' => 'btn-danger']) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="mod-section">
    <h2>🎨 Artistes en attente (<?= count($artists) ?>)</h2>
    <?php foreach ($artists as $a): ?>
        <div class="mod-card">
            <div class="title"><?= e($a['name']) ?> <span class="muted small">— <?= e($a['disciplines'] ?? '') ?></span></div>
            <div class="meta">proposé par <?= e($a['submitter'] ?? '?') ?> · <?= e($a['created_at']) ?></div>
            <div class="mod-actions">
                <?= $mc(['action' => '?r=/moderation/artistes/' . (int) $a['id'] . '/approve', 'label' => '✓ Valider', 'class' => 'btn-primary']) ?>
                <?= $mc(['action' => '?r=/moderation/artistes/' . (int) $a['id'] . '/reject', 'label' => '✗ Refuser', 'class' => 'btn-danger']) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="mod-section">
    <h2>🔑 Revendications (<?= count($claims) ?>)</h2>
    <?php foreach ($claims as $cl): ?>
        <div class="mod-card">
            <div class="title"><?= e($cl['kind']) ?> : <?= e($cl['entity_name'] ?? '#' . $cl['entity_id']) ?></div>
            <div class="meta">demandé par <?= e($cl['username'] ?? $cl['email'] ?? '?') ?> — <?= e($cl['justification'] ?? '') ?></div>
            <div class="mod-actions">
                <?= $mc(['action' => '?r=/moderation/claims/' . (int) $cl['id'] . '/approve', 'label' => '✓ Approuver', 'class' => 'btn-primary']) ?>
                <?= $mc(['action' => '?r=/moderation/claims/' . (int) $cl['id'] . '/reject', 'label' => '✗ Refuser', 'class' => 'btn-danger']) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="mod-section">
    <h2>🚩 Signalements (<?= count($reports) ?>)</h2>
    <?php foreach ($reports as $r): ?>
        <div class="mod-card">
            <div class="title"><?= e($r['kind']) ?> #<?= (int) $r['item_id'] ?></div>
            <div class="meta">par <?= e($r['username'] ?? $r['email'] ?? 'anonyme') ?> — <?= e($r['reason']) ?></div>
            <div class="mod-actions">
                <?= $mc(['action' => '?r=/moderation/reports/' . (int) $r['id'] . '/resolve', 'label' => 'Résoudre (masquer le contenu)', 'class' => 'btn-primary']) ?>
                <?= $mc(['action' => '?r=/moderation/reports/' . (int) $r['id'] . '/resolve', 'label' => 'Ignorer', 'class' => 'btn-outline', 'hidden' => ['<input type="hidden" name="action" value="dismiss">']]) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="mod-section">
    <h2>💡 Suggestions (<?= count($suggestions) ?>)</h2>
    <?php foreach ($suggestions as $s): ?>
        <div class="mod-card">
            <div class="title"><?= e($s['kind']) ?> #<?= (int) $s['item_id'] ?></div>
            <div class="meta"><?= e($s['content']) ?></div>
            <div class="mod-actions">
                <?= $mc(['action' => '?r=/moderation/suggestions/' . (int) $s['id'] . '/resolve', 'label' => 'Traité', 'class' => 'btn-primary']) ?>
                <?= $mc(['action' => '?r=/moderation/suggestions/' . (int) $s['id'] . '/resolve', 'label' => 'Ignorer', 'class' => 'btn-outline', 'hidden' => ['<input type="hidden" name="action" value="dismiss">']]) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="mod-section">
    <h2>📜 Journal</h2>
    <div class="table-wrap">
        <table class="list">
            <thead><tr><th>Quand</th><th>Qui</th><th>Action</th><th>Cible</th><th>Détails</th></tr></thead>
            <tbody>
            <?php foreach ($journal as $j): ?>
                <tr>
                    <td class="small"><?= e($j['created_at']) ?></td>
                    <td><?= e($j['username'] ?? '?') ?></td>
                    <td><?= e($j['action']) ?></td>
                    <td><?= e($j['kind']) ?> #<?= (int) $j['item_id'] ?></td>
                    <td class="small muted"><?= e($j['details'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>