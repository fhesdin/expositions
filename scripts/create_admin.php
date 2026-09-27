#!/usr/bin/env php
<?php
/**
 * Création d'un compte admin : php scripts/create_admin.php <username> <email> <password>
 * Usage : docker exec web-stack-php-1 php /var/www/expositions/scripts/create_admin.php admin admin@expositions.top 'MotDePasse!123'
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/core/Env.php';
\Core\Env::load($root . '/.env');

if ($argc < 4) {
    fwrite(STDERR, "Usage : php create_admin.php <username> <email> <password>\n");
    exit(1);
}
[$_, $username, $email, $password] = $argv;

if (strlen($password) < 10) {
    fwrite(STDERR, "Mot de passe trop court (min. 10 caractères).\n");
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', \Core\Env::get('DB_HOST'), \Core\Env::get('DB_PORT'), \Core\Env::get('DB_NAME')),
    \Core\Env::get('DB_USER'), \Core\Env::get('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
$exists->execute([$email, $username]);
if ($exists->fetch()) {
    echo "Un compte avec cet e-mail ou pseudo existe déjà.\n";
    exit(1);
}

$slug = $username;
$st = $pdo->prepare('INSERT INTO users (username, email, password_hash, role_id, is_active, created_at) VALUES (?, ?, ?, 5, 1, NOW())');
$st->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
echo "Admin créé : #$username (id " . $pdo->lastInsertId() . ", rôle 5).\n";
