<?php /** Pages statiques simples (à propos, charte, mentions légales, confidentialité) */
$t = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_URL) ?: '', '');
$simple = [
    'a-propos' => ['À propos', "expositions.top est un projet associatif : recenser TOUTES les expositions de la Somme, qu'elles aient lieu dans un grand musée ou dans une médiathèque de village. L'information y est gratuite, vérifiée par une communauté de contributeurs et une équipe de modération."],
    'charte' => ['Charte de contribution', "Contribuez avec des informations exactes et sourcées. Pas de contenu promotionnel excessif, pas de photos sans autorisation. Toute fiche est vérifiée avant publication ; les contributeurs réguliers gagnent des droits élargis."],
    'mentions-legales' => ['Mentions légales', "Éditeur : association expositions.top — contact via le formulaire du site. Hébergement : serveur personnel. Le site ne dépose pas de cookies publicitaires ; seules des cookies techniques de session sont utilisés."],
    'confidentialite' => ['Confidentialité', "Données collectées : e-mail (compte/newsletter), pseudo, commune facultative. Jamais cédées à des tiers. Suppression du compte sur simple demande. Les statistiques de visite sont anonymes et agrégées."],
];
$key = $_GET['p'] ?? 'a-propos';
[$title, $text] = $simple[$key] ?? $simple['a-propos'];
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<div class="form-card"><p><?= e($text) ?></p></div>