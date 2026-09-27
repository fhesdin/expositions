<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Category extends Model
{
    protected static string $table = 'categories';

    public static function allActive(): array
    {
        return Database::run('SELECT * FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
    }
}
