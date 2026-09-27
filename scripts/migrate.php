#!/usr/bin/env php
<?php
/**
 * Runner de migrations — expositions.top
 * Applique les .sql de migrations/ non journalisés dans la table migrations.
 * Usage : docker exec web-stack-php-1 php /var/www/expositions/scripts/migrate.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/core/Env.php';
\Core\Env::load($root . '/.env');

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    \Core\Env::get('DB_HOST', 'mysql_db'), \Core\Env::get('DB_PORT', '3306'), \Core\Env::get('DB_NAME'));
$pdo = new PDO($dsn, \Core\Env::get('DB_USER'), \Core\Env::get('DB_PASS'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(190) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$done = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob($root . '/migrations/*.sql');
sort($files);
$applied = 0;

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        echo "  = $name (déjà appliquée)\n";
        continue;
    }
    echo "→ $name … ";
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        $st = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $st->execute([$name]);
        echo "OK\n";
        $applied++;
    } catch (PDOException $e) {
        echo "ÉCHEC : " . $e->getMessage() . "\n";
        exit(1);
    }
}
echo $applied === 0 ? "Aucune nouvelle migration.\n" : "$applied migration(s) appliquée(s).\n";
