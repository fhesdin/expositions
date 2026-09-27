# expositions.top — la Somme

Annuaire et gestion des expositions du département de la Somme (80) : musées, galeries,
médiathèques, châteaux, ateliers d'artistes. Application PHP 8.2 MVC maison, fork du
framework interne de *pokemon-go-manager*, hébergée sur le stack docker `web-stack`
(srv1231763.hstgr.cloud).

## URL publique

`https://srv1231763.hstgr.cloud/expositions/` (routage `?r=/route`)

## Fonctionnalités (MVP)

- **Consultation** : expositions en cours / à venir / archives, fiches lieux et artistes,
  carte Leaflet, calendrier mensuel, autocomplétion de recherche, export iCal
  (expo, agenda personnel), filtres combinables (commune, catégorie, tag, type, PMR,
  gratuité, période).
- **Contribution** : compte membre → contribution (créer/éditer lieux, artistes,
  expositions, événements de type vernissage), proposition **sans compte** modérée a priori,
  duplication d'exposition en brouillon.
- **Communauté** : favoris, « J'y vais » / « J'y suis allé » avec compteurs, newsletter
  (double opt-in, désinscription), réaction à une expo hebdomadaire (`notify_weekly`).
- **Modération** : file d'attente expositions / lieux / artistes / suggestions / claims,
  approbation, rejet motivé, dépublication, journal d'actions (`mod_log`).
- **Admin** : utilisateurs (rôles, activation), taxonomie (catégories, types de lieux,
  publics, labels), communes, paramètres, statistiques.
- **Rôles cumulatifs** : visiteur(1) < membre(2) < contributeur(3) < modérateur(4) < admin(5).

## Architecture

```
index.php            front controller + autoloader (namespace App\ → modules/, Core\ → core/)
config/routes.php    table de routage (statiques AVANT dynamiques)
core/                framework (fork pokemon-go-manager ; session expo_sess/expo_remember)
modules/             Auth, Expositions, Places, Artists, Moderation, Community, Admin, Home, Models
templates/           layout unique + templates par module (mobile-first)
migrations/          001 schéma, 002 taxonomy, 003 communes (runner journalisé)
scripts/             migrate.php, create_admin.php, seed_demo.php, archive_expos.php (cron nocturne)
css/, js/            design system (terracotta + bleu nuit), Leaflet
```

## Installation

1. Base MySQL : créer la base `expositions` (utf8mb4_unicode_ci) et un utilisateur dédié.
2. Copier `.env.example` → `.env` et renseigner DB_*, APP_URL, MAIL_*.
3. Migrations : `php scripts/migrate.php` (journalise dans la table `migrations`).
4. Compte admin : `php scripts/create_admin.php <pseudo> <email> <motdepasse>`.
5. Données de démonstration (optionnel) : `php scripts/seed_demo.php`.
6. Cron : archivage automatique des expositions terminées (>30 j, sauf permanentes) —
   `php scripts/archive_expos.php` une fois par nuit.

## Notes techniques

- PHP 8.2 ; extensions requises : pdo_mysql, mbstring, gd. **Pas d'ext-intl** :
  le formatage des dates est fait maison (`ExhibitionModel::datesHuman()`).
- Sessions : `expo_sess` / `expo_remember` (remember-me 30 j, sélecteur+validateur).
- Mots de passe : `password_hash()` (bcrypt). Mails : SMTP via `core/Mailer.php`,
  ou log fichier en APP_DEBUG sans SMTP_HOST.
- Suggest/search : JSON renvoyé par `HomeController::searchSuggest`.

## Git

Branche `main`. Les secrets (`.env`) ne sont pas versionnés.
