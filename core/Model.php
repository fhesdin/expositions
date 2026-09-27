<?php

namespace Core;

/**
 * Base CRUD générique pour les modèles.
 * Les classes filles définissent $table et $pk.
 */
abstract class Model
{
    protected static string $table;
    protected static string $pk = 'id';

    public static function find(int|string $id): ?array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' WHERE ' . static::$pk . ' = ? LIMIT 1';
        $row = Database::run($sql, [$id])->fetch();
        return $row === false ? null : $row;
    }

    public static function all(): array
    {
        return Database::run('SELECT * FROM ' . static::$table)->fetchAll();
    }

    public static function create(array $data): int|string
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO ' . static::$table .
               ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
        Database::run($sql, $data);
        return Database::lastInsertId();
    }

    public static function update(int|string $id, array $data): bool
    {
        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $sql = 'UPDATE ' . static::$table . ' SET ' . implode(', ', $sets) .
               ' WHERE ' . static::$pk . ' = :_pk';
        $data['_pk'] = $id;
        return Database::run($sql, $data)->rowCount() >= 0;
    }

    public static function delete(int|string $id): bool
    {
        $sql = 'DELETE FROM ' . static::$table . ' WHERE ' . static::$pk . ' = ?';
        return Database::run($sql, [$id])->rowCount() > 0;
    }

    public static function count(): int
    {
        return (int) Database::run('SELECT COUNT(*) FROM ' . static::$table)->fetchColumn();
    }
}
