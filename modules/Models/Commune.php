<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Commune extends Model
{
    protected static string $table = 'communes';

    public static function allOrdered(): array
    {
        return Database::run('SELECT * FROM communes ORDER BY nom')->fetchAll();
    }

    public static function findByInsee(string $insee): ?array
    {
        $row = Database::run('SELECT * FROM communes WHERE code_insee = ? LIMIT 1', [$insee])->fetch();
        return $row === false ? null : $row;
    }
}
