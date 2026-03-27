<?php namespace App\Libraries;

class SchemaHelper
{
    protected static array $cache = [];

    public static function getEnumValues(string $table, string $column): array
    {
        $key = "{$table}.{$column}";
        if (isset(static::$cache[$key])) return static::$cache[$key];

        $db    = \Config\Database::connect();
        $query = $db->query(
            "SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]
        );
        $row = $query->getRowArray();

        if (!$row) return static::$cache[$key] = [];

        preg_match('/^enum\((.+)\)$/', $row['Type'], $matches);
        if (empty($matches[1])) return static::$cache[$key] = [];

        return static::$cache[$key] = array_map(
            fn($v) => trim($v, "'"),
            explode(',', $matches[1])
        );
    }
}