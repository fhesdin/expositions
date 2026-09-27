<?php /** Partielle : carte d'une exposition (réutilisable listes/accueil) */
if (!function_exists('expo_badges')) {
    function expo_badges(array $e): string {
        $b = [];
        $today = date('Y-m-d');
        if ((int)($e['is_permanent'] ?? 0) === 1) {
            $b[] = '<span class="badge encours">Permanente</span>';
        } elseif (!empty($e['ends_at']) && $e['ends_at'] < $today) {
            $b[] = '<span class="badge terminee">Terminée</span>';
        } elseif (!empty($e['starts_at']) && $e['starts_at'] > $today) {
            $b[] = '<span class="badge a-venir">À venir</span>';
            if (!empty($e['created_at']) && strtotime($e['created_at']) > strtotime('-14 days')) {
                $b[] = '<span class="badge nouveau">Nouveau</span>';
            }
        } else {
            $b[] = '<span class="badge encours">En cours</span>';
            if (!empty($e['ends_at']) && strtotime($e['ends_at']) <= strtotime('+7 days')) {
                $b[] = '<span class="badge dernier">Derniers jours</span>';
            }
        }
        if (($e['price_kind'] ?? '') === 'gratuit') { $b[] = '<span class="badge gratuit">Gratuit</span>'; }
        if (in_array($e['status'] ?? '', ['cancelled', 'postponed'], true)) {
            $b[] = '<span class="badge annulee">' . ($e['status'] === 'cancelled' ? 'Annulée' : 'Reportée') . '</span>';
        }
        return implode('', $b);
    }
}
if (!function_exists('expo_dates')) {
    function expo_dates(array $e): string {
        if ((int)($e['is_permanent'] ?? 0) === 1) { return 'Exposition permanente'; }
        $f = fn(?string $d) => $d ? date('d/m/Y', strtotime($d)) : '';
        if (empty($e['starts_at'])) { return 'Dates à confirmer'; }
        if (empty($e['ends_at'])) { return 'À partir du ' . $f($e['starts_at']); }
        return $f($e['starts_at']) . ' → ' . $f($e['ends_at']);
    }
}
$e = $expo;
$catColor = $e['category_color'] ?? '#b4552d';
$poster = !empty($e['poster_path']) ? e($e['poster_path']) : null;
?>
<article class="card">
    <a class="media" href="?r=/expositions/<?= e($e['slug']) ?>" style="<?= $poster ? "background-image:url('" . $poster . "')" : '' ?>">
        <?php if (!empty($e['category_name'])): ?>
            <span class="cat-chip" style="border-left-color:<?= e($catColor) ?>"><?= e($e['category_name']) ?></span>
        <?php endif; ?>
    </a>
    <div class="body">
        <h3><a href="?r=/expositions/<?= e($e['slug']) ?>"><?= e($e['title']) ?></a></h3>
        <div class="meta">
            <span><?= e(expo_dates($e)) ?></span>
            <?php if (!empty($e['place_name'])): ?><span>📍 <?= e($e['place_name']) ?></span><?php endif; ?>
            <?php if (!empty($e['commune_name'])): ?><span><?= e($e['commune_name']) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($e['summary'])): ?><p class="summary"><?= e(excerpt($e['summary'], 110)) ?></p><?php endif; ?>
        <div class="badges"><?= expo_badges($e) ?></div>
    </div>
</article>