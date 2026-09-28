<?php
/** @var string $content  contenu de la vue */
$currentUser = $currentUser ?? null;
$siteName = 'expositions.top — la Somme';
$flash = \Core\Session::flushFlashes();
$pendingCount = ($currentUser !== null && \Core\Auth::isModerator())
    ? (int) \Core\Database::run(
        'SELECT
           (SELECT COUNT(*) FROM exhibitions WHERE status = "pending") +
           (SELECT COUNT(*) FROM places WHERE status = "pending") +
           (SELECT COUNT(*) FROM artists WHERE status = "pending") +
           (SELECT COUNT(*) FROM claims WHERE status = "pending") +
           (SELECT COUNT(*) FROM reports WHERE status = "open") +
           (SELECT COUNT(*) FROM suggestions WHERE status = "open")'
    )->fetchColumn()
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? $siteName) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'Toutes les expositions de la Somme : musées, galeries, médiathèques, ateliers d\'artistes. Recherche par date, lieu, catégorie. Gratuit.') ?>">
    <?php if (!empty($openGraph)): ?>
    <meta property="og:title" content="<?= e($openGraph['title'] ?? $title) ?>">
    <meta property="og:description" content="<?= e($openGraph['description'] ?? '') ?>">
    <?php if (!empty($openGraph['image'])): ?><meta property="og:image" content="<?= e($openGraph['image']) ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css">
    <?php if (!empty($leaflet)): ?>
    <link rel="stylesheet" href="assets/leaflet/leaflet.css">
    <script src="assets/leaflet/leaflet.js"></script>
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <div class="inner">
        <a class="brand" href="?r=/"><span class="dot"></span>expositions<em style="font-style:normal;color:var(--c-primary)">.top</em></a>
        <nav class="main-nav">
            <a href="?r=/expositions">Expositions</a>
            <a href="?r=/carte">Carte</a>
            <a href="?r=/calendrier">Calendrier</a>
            <a href="?r=/lieux">Lieux</a>
            <a href="?r=/artistes">Artistes</a>
            <?php if ($currentUser === null): ?>
                <a href="?r=/expositions%2Fpropose">Proposer une expo</a>
                <a href="?r=/login">Connexion</a>
            <?php else: ?>
                <?php if (\Core\Auth::isContributor() || \Core\Auth::isModerator()): ?>
                    <a href="?r=/expositions%2Fcreate">+ Expo</a>
                <?php endif; ?>
                <?php if (\Core\Auth::isModerator()): ?>
                    <a href="?r=/moderation">Modération<?php if ($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?></a>
                <?php endif; ?>
                <?php if (\Core\Auth::isAdmin()): ?>
                    <details class="dropdown">
                        <summary>Admin ▾</summary>
                        <div class="dropdown-menu">
                            <a href="?r=/admin%2Fusers">Utilisateurs</a>
                            <a href="?r=/admin%2Ftaxonomy">Taxonomie</a>
                            <a href="?r=/admin%2Fcommunes">Communes</a>
                            <a href="?r=/admin%2Fstats">Statistiques</a>
                            <a href="?r=/admin%2Fsettings">Paramètres</a>
                        </div>
                    </details>
                <?php endif; ?>
                <details class="dropdown">
                    <summary><?= e($currentUser['username']) ?> ▾</summary>
                    <div class="dropdown-menu">
                        <a href="?r=/mon-espace">Mon espace</a>
                        <a href="?r=/profile">Mon profil</a>
                        <a href="?r=/logout">Déconnexion</a>
                    </div>
                </details>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <?php if (!empty($flash)): foreach ($flash as $msg): ?>
        <div class="flash flash-<?= e($msg['type']) ?>"><?= e($msg['message']) ?></div>
    <?php endforeach; endif; ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="inner">
        <div style="max-width:320px">
            <h4>expositions.top</h4>
            <p>Le recensement gratuit des expositions de la Somme, porté par une association. Un lieu manquant ? Une erreur ? <a href="?r=/expositions%2Fpropose">Signalez-le</a>.</p>
        </div>
        <div>
            <h4>Explorer</h4>
            <ul>
                <li><a href="?r=/expositions">Toutes les expositions</a></li>
                <li><a href="?r=/expositions&period=week-end">Ce week-end</a></li>
                <li><a href="?r=/lieux">Annuaire des lieux</a></li>
                <li><a href="?r=/artistes">Annuaire des artistes</a></li>
            </ul>
        </div>
        <div>
            <h4>L'association</h4>
            <ul>
                <li><a href="?r=/page%2Fa-propos">À propos</a></li>
                <li><a href="?r=/page%2Fcharte">Charte de contribution</a></li>
                <li><a href="?r=/page%2Fmentions-legales">Mentions légales</a></li>
                <li><a href="?r=/page%2Fconfidentialite">Confidentialité</a></li>
            </ul>
        </div>
        <div>
            <h4>Newsletter</h4>
            <p style="margin:.2rem 0 0">Vernissages et dernières chances, chaque semaine.</p>
            <form method="post" action="?r=/newsletter%2Fsubscribe" class="newsletter-form">
                <input type="email" name="email" placeholder="votre@email.fr" required aria-label="Adresse e-mail">
                <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
                <button type="submit">OK</button>
            </form>
        </div>
    </div>
</footer>

<script src="js/app.js"></script>
</body>
</html>