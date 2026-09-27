<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Singleton de connexion PDO à MySQL.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::getInt('DB_PORT', 3306);
        $name = Env::get('DB_NAME', 'pokemon_go_manager');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ];
        // La session MySQL doit utiliser le fuseau de l'application (APP_TIMEZONE, ex. Europe/Paris)
        // pour que NOW() / CURRENT_TIMESTAMP soient cohérents avec les dates stockées (heure locale).
        // Sinon les comparaisons (starts_at > NOW(), statut, syncStatuses) sont décalées de l'offset UTC.
        $tzOffset = (new \DateTime('now'))->format('P'); // ex. "+02:00"
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES $charset, time_zone = '$tzOffset'";
        }

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Erreur de connexion à la base de données.');
        }
        return self::$pdo;
    }

    /** Exécute une requête préparée et retourne le statement. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        if (array_is_list($params)) {
            // Paramètres positionnels (?)
            foreach ($params as $i => $val) {
                $param = $i + 1;
                if (is_int($val)) {
                    $stmt->bindValue($param, $val, PDO::PARAM_INT);
                } elseif ($val === null) {
                    $stmt->bindValue($param, $val, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue($param, $val);
                }
            }
            $stmt->execute();
        } else {
            // Paramètres nommés (:name)
            foreach ($params as $key => $val) {
                if (is_int($val)) {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                } elseif ($val === null) {
                    $stmt->bindValue($key, $val, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue($key, $val);
                }
            }
            $stmt->execute();
        }
        return $stmt;
    }

    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }
}
