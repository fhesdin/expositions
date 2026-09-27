#!/usr/bin/env php
<?php
/**
 * Archivage automatique : passe en status=archived les expositions dont
 * ends_at < aujourd'hui - 30 jours (sauf permanentes). À exécuter chaque nuit.
 * Crée la table d'ordonnancement si besoin (cron auto-régistré).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/core/Env.php';
\Core\Env::load($root . '/.env');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', \Core\Env::get('DB_HOST'), \Core\Env::get('DB_PORT'), \Core\Env::get('DB_NAME')),
    \Core\Env::get('DB_USER'), \Core\Env::get('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$st = $pdo->prepare('UPDATE exhibitions SET status = "archived", updated_at = NOW()
                     WHERE status = "published" AND is_permanent = 0
                       AND ends_at IS NOT NULL AND ends_at < CURDATE() - INTERVAL 30 DAY');
$st->execute();
echo date('Y-m-d H:i') . " — {$st->rowCount()} exposition(s) archivée(s).\n";
