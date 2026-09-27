<?php /** Vue calendrier mensuelle (périodes + vernissages, légende par catégorie) */
$monthLabel = $monthStart->format('m/Y');
$frMonths = [1=>'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
[$y, $m] = [(int) $monthStart->format('Y'), (int) $monthStart->format('n')];
$prev = $monthStart->modify('-1 month')->format('Y-m');
$next = \DateTime::createFromFormat('Y-m', "$y-$m")->modify('+1 month')->format('Y-m');
$prevLabel = $frMonths[(int)\DateTime::createFromFormat('Y-m', $prev)->format('n')] . ' ' . \DateTime::createFromFormat('Y-m', $prev)->format('Y');
$nextLabel = $frMonths[(int)\DateTime::createFromFormat('Y-m', $next)->format('n')] . ' ' . \DateTime::createFromFormat('Y-m', $next)->format('Y');
$first = \DateTime::createFromFormat('Y-m-d', $monthStart->format('Y-m-01'));
// lundi = 0 : décalage ISO
$offset = ((int) $first->format('N')) - 1;
$daysInMonth = (int) $first->format('t');
$cells = [];
for ($i = 0; $i < $offset; $i++) { $cells[] = null; }
for ($d = 1; $d <= $daysInMonth; $d++) { $cells[] = \DateTime::createFromFormat('Y-m-d', $first->format('Y-m-') . str_pad((string)$d, 2, '0', STR_PAD_LEFT)); }
while (count($cells) % 7 !== 0) { $cells[] = null; }
// index d'événements par jour
$byDay = [];
foreach ($calItems as $it) {
    if (empty($it['date_key'])) { continue; }
    $byDay[$it['date_key']][] = $it;
}
$dows = ['lun', 'mar', 'mer', 'jeu', 'ven', 'sam', 'dim'];
$today = date('Y-m-d');
?>
<div class="page-head">
    <h1>Calendrier des expositions</h1>
    <p class="sub">Périodes d'ouverture (barres par catégorie) et événements ponctuels.</p>
</div>

<div class="calendar">
    <div class="cal-head">
        <a class="btn btn-outline btn-sm" href="?r=/calendrier&amp;month=<?= e($prev) ?>">← <?= e($prevLabel) ?></a>
        <h2><?= e($frMonths[$m]) ?> <?= $y ?></h2>
        <a class="btn btn-outline btn-sm" href="?r=/calendrier&amp;month=<?= e($next) ?>"><?= e($nextLabel) ?> →</a>
    </div>
    <div class="cal-grid">
        <?php foreach ($dows as $dow): ?><div class="dow"><?= e($dow) ?></div><?php endforeach; ?>
        <?php foreach ($cells as $cell): ?>
            <?php if ($cell === null): ?><div class="cal-cell out"></div>
            <?php else:
                $key = $cell->format('Y-m-d'); ?>
                <div class="cal-cell <?= $key === $today ? 'today' : '' ?>">
                    <span class="d"><?= (int) $cell->format('j') ?></span>
                    <?php foreach ($byDay[$key] ?? [] as $it): ?>
                        <?php if ($it['kind'] === 'event'): ?>
                            <a class="ev" style="background:#1d2b3a" title="<?= e($it['label']) ?>" href="?r=/expositions/<?= e($it['slug']) ?>">🥂 <?= e($it['label']) ?></a>
                        <?php else: ?>
                            <a class="ev" style="background:<?= e($it['color']) ?>" title="<?= e($it['label']) ?>" href="?r=/expositions/<?= e($it['slug']) ?>"><?= e($it['label']) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="cal-legend">
        <?php foreach ($legend as $c): ?>
            <span><span class="dot" style="background:<?= e($c['color']) ?>"></span><?= e($c['name']) ?></span>
        <?php endforeach; ?>
        <span><span class="dot" style="background:#1d2b3a"></span>Événement (vernissage…)</span>
    </div>
</div>
<p class="mt small muted">Astuce : sur la fiche d'une expo, « Ajouter à mon agenda (.ics) » exporte l'événement pour votre agenda personnel.</p>