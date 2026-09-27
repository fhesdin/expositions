#!/usr/bin/env php
<?php
/**
 * Seed de démonstration : lieux et expositions de la Somme (données réelles publiques).
 * Colonne par colonne conforme au schéma réel (001_schema.sql appliqué).
 * Usage : docker exec web-stack-php-1 php /var/www/html/expositions/scripts/seed_demo.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/core/Env.php';
\Core\Env::load($root . '/.env');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', \Core\Env::get('DB_HOST'), \Core\Env::get('DB_PORT'), \Core\Env::get('DB_NAME')),
    \Core\Env::get('DB_USER'), \Core\Env::get('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function slug(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
function communeId(PDO $pdo, string $nom): ?int {
    $st = $pdo->prepare('SELECT id FROM communes WHERE nom = ?');
    $st->execute([$nom]);
    $id = $st->fetchColumn();
    return $id === false ? null : (int) $id;
}

// ---- lieux (données publiques : nom, commune, lat, lon, site) ----
// colonnes réelles : slug name type_id commune_id address website opening_hours status created_by
$places = [
    ['Musée de Picardie', 'Amiens', 49.8945, 2.2986, 'https://museepicardie.fr', 'musee', '48 rue de la République', 'mer.–lun. 9h30–18h, fermé mardi'],
    ['Carré K.', 'Amiens', 49.8899, 2.2957, 'https://www.carrek.fr', 'galerie', 'place Léon Gontier', 'mer.–sam. 14h–18h'],
    ['Maison de la Culture d’Amiens', 'Amiens', 49.8924, 2.2930, 'https://maisondelaculture80.fr', 'centre_culturel', 'place Léon Gontier', 'selon programmation'],
    ['Musée Boucher-de-Perthes', 'Abbeville', 50.1055, 1.8372, 'https://www.abbeville.fr', 'musee', '24 place du Général de Gaulle', 'mer.–lun. 10h–12h / 14h–18h'],
    ['Musée municipal de Péronne', 'Péronne', 49.9286, 2.8842, '', 'musee', 'place André Audinot', 'mer.–lun. 14h–18h'],
    ['Historial de la Grande Guerre', 'Péronne', 49.9288, 2.8888, 'https://historial.fr', 'musee', 'château de Péronne', 'tous les jours 9h30–18h'],
    ['Musée Hospitalier de Doullens', 'Doullens', 50.1586, 2.3428, '', 'musee', 'ancien hôpital', 'sur rendez-vous'],
    ['Château de Rambures', 'Rambures', 49.9978, 1.7808, 'https://chateauderambures.fr', 'chateau', '5 rue du Château', 'avr.–oct. 14h–18h'],
    ['Médiathèque de Corbie', 'Corbie', 49.9106, 2.5167, '', 'mediatheque', '7 place de la République', 'mar.–sam.'],
    ['Espace Bailleul', 'Amiens', 49.8833, 2.3020, '', 'salle_exposition', '6 rue Bailleul', 'lun.–sam. 10h–18h'],
    ['Galerie de l’Estuaire', 'Ault', 50.1047, 1.4600, '', 'galerie', 'rue de l’Église', 'juil.–sept. tous les jours'],
    ['Musée d’Albert', 'Albert', 50.0019, 2.6525, '', 'musee', 'rue Gambetta', 'mer.–lun. 14h–18h'],
];

$placeIds = [];
$stType = $pdo->prepare('SELECT id FROM place_types WHERE slug = ?');
foreach ($places as [$name, $commune, $lat, $lon, $site, $typeSlug, $address, $hours]) {
    $slugName = slug($name);
    $st = $pdo->prepare('SELECT id FROM places WHERE slug = ?');
    $st->execute([$slugName]);
    $id = $st->fetchColumn();
    if ($id === false) {
        $stType->execute([$typeSlug]);
        $typeId = $stType->fetchColumn() ?: null;
        $ins = $pdo->prepare('INSERT INTO places (slug, name, type_id, commune_id, address, latitude, longitude, website, opening_hours, status, created_by, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "published", 1, NOW())');
        $ins->execute([$slugName, $name, $typeId, communeId($pdo, $commune), $address, $lat, $lon, $site ?: null, $hours]);
        $id = $pdo->lastInsertId();
    }
    $placeIds[$name] = (int) $id;
}
echo count($placeIds) . " lieux prêts.\n";

// ---- expositions de démonstration (calées autour d'aujourd'hui) ----
// colonnes réelles : slug title category_id place_id commune_id summary description
//                    starts_at ends_at is_permanent opening_hours price_kind price_detail status submitted_by
$today = new DateTimeImmutable('today');
$d = fn(int $offset) => $today->modify("{$offset} days")->format('Y-m-d');

$expos = [
    ['title' => 'Picardie en couleurs — peintres de la Somme', 'place' => 'Musée de Picardie', 'commune' => 'Amiens',
     'cat' => 'peinture', 'summary' => 'Un siècle de paysages picards par les peintres de la région : de la baie de Somme aux hortillonnages.',
     'desc' => 'Parcours chronologique en trois salles. Œuvres issues des collections permanentes et de prêts privés.',
     'start' => $d(-60), 'end' => $d(30), 'perm' => 0, 'price' => 'gratuit', 'detail' => 'Gratuit -18 ans et premier dimanche du mois. Plein tarif 8 €.', 'hours' => 'mer.–lun. 9h30–18h'],
    ['title' => 'Photographier la Baie — regards contemporains', 'place' => 'Carré K.', 'commune' => 'Amiens',
     'cat' => 'photographie', 'summary' => 'Six photographes témoignent de la baie de Somme : phoques, pêcheurs, lumière changeante.',
     'desc' => 'En partenariat avec la Maison du Port. Tirages grand format, entrée libre.',
     'start' => $d(-10), 'end' => $d(5), 'perm' => 0, 'price' => 'gratuit', 'detail' => null, 'hours' => 'mer.–sam. 14h–18h'],
    ['title' => 'Guérres et Paix — collections de l’Historial', 'place' => 'Historial de la Grande Guerre', 'commune' => 'Péronne',
     'cat' => 'histoire', 'summary' => 'La Grande Guerre racontée par les objets et les témoignages des trois belligérants.',
     'desc' => 'Scénographie rénovée. Audioguide inclus dans le billet.',
     'start' => $d(-400), 'end' => null, 'perm' => 1, 'price' => 'payant', 'detail' => 'Plein tarif 10 €, réduit 7 €.', 'hours' => 'tous les jours 9h30–18h'],
    ['title' => 'Artisanat et art populaire du Vimeu', 'place' => 'Musée Boucher-de-Perthes', 'commune' => 'Abbeville',
     'cat' => 'artisanat', 'summary' => 'Serrurerie d’art, sabots et faïences : l’artisanat du Vimeu à l’honneur.',
     'desc' => 'Démonstrations le week-end.',
     'start' => $d(20), 'end' => $d(80), 'perm' => 0, 'price' => 'payant', 'detail' => 'Plein tarif 5 €.', 'hours' => 'mer.–lun. 10h–12h / 14h–18h'],
    ['title' => 'Dessins du château — cabinet d’arts graphiques', 'place' => 'Château de Rambures', 'commune' => 'Rambures',
     'cat' => 'dessin', 'summary' => 'Dessins et estampes des collections du château, présentés dans la galerie voûtée.',
     'desc' => null,
     'start' => $d(5), 'end' => $d(45), 'perm' => 0, 'price' => 'gratuit', 'detail' => 'Gratuit moins de 12 ans. Plein tarif 7 €.', 'hours' => 'avr.–oct. 14h–18h'],
    ['title' => 'Créations d’ici — la médiathèque expose', 'place' => 'Médiathèque de Corbie', 'commune' => 'Corbie',
     'cat' => 'art_contemporain', 'summary' => 'Artistes amateurs et professionnels de la commune partagent leurs dernières créations.',
     'desc' => null,
     'start' => $d(-30), 'end' => $d(-2), 'perm' => 0, 'price' => 'gratuit', 'detail' => null, 'hours' => 'horaires de la médiathèque'],
];

$stCat = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
foreach ($expos as $x) {
    $slug = slug($x['title']);
    $st = $pdo->prepare('SELECT id FROM exhibitions WHERE slug = ?');
    $st->execute([$slug]);
    if ($st->fetchColumn() !== false) { continue; }
    $stCat->execute([$x['cat']]);
    $catId = $stCat->fetchColumn() ?: null;
    $ins = $pdo->prepare('INSERT INTO exhibitions
        (slug, title, category_id, place_id, commune_id, summary, description, starts_at, ends_at, is_permanent,
         opening_hours, price_kind, price_detail, status, submitted_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published", 1, NOW(), NOW())');
    $ins->execute([$slug, $x['title'], $catId, $placeIds[$x['place']], communeId($pdo, $x['commune']),
        $x['summary'], $x['desc'], $x['start'], $x['end'], $x['perm'], $x['hours'], $x['price'], $x['detail']]);
}
echo "Seed expositions terminé.\n";
