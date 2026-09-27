<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Tag extends Model
{
    protected static string $table = 'tags';

    public static function search(string $q, int $limit = 10): array
    {
        return Database::run(
            'SELECT * FROM tags WHERE name LIKE ? ORDER BY name LIMIT ' . (int) $limit,
            ['%' . $q . '%']
        )->fetchAll();
    }

    public static function findBySlugOrCreate(string $name, bool $approved = false): ?array
    {
        $slug = slugify($name);
        $row = Database::run('SELECT * FROM tags WHERE slug = ? LIMIT 1', [$slug])->fetch();
        if ($row !== false) {
            return $row;
        }
        Database::run('INSERT INTO tags (slug, name, is_approved) VALUES (?, ?, ?)', [$slug, $name, (int) $approved]);
        return Database::run('SELECT * FROM tags WHERE slug = ? LIMIT 1', [$slug])->fetch();
    }
}
