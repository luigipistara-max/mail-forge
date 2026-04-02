<?php

declare(strict_types=1);

namespace MailForge\Models;

class Setting extends BaseModel
{
    protected static string $table = 'settings';
    protected static array $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /** In-memory cache for the current request. */
    private static array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $table = $this->getTable();
        $sql   = "SELECT `value`, `type` FROM `{$table}` WHERE `key` = :key LIMIT 1";

        $stmt = $this->executeQuery($sql, [':key' => $key]);
        $row  = $stmt->fetch();

        if ($row === false) {
            return $default;
        }

        $value = $this->castValue($row['value'], $row['type'] ?? 'string');
        self::$cache[$key] = $value;

        return $value;
    }

    public function set(string $key, mixed $value, string $type = 'string'): bool
    {
        $table       = $this->getTable();
        $storedValue = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;

        $sql = "INSERT INTO `{$table}` (`key`, `value`, `type`)
                VALUES (:key, :value, :type)
                ON DUPLICATE KEY UPDATE `value` = :value_upd, `type` = :type_upd";

        $stmt = $this->executeQuery($sql, [
            ':key'       => $key,
            ':value'     => $storedValue,
            ':type'      => $type,
            ':value_upd' => $storedValue,
            ':type_upd'  => $type,
        ]);

        // Invalidate cache entry.
        unset(self::$cache[$key]);

        return $stmt->rowCount() > 0;
    }

    public function getGroup(string $group): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `group` = :group ORDER BY `key` ASC";

        $rows   = $this->executeQuery($sql, [':group' => $group])->fetchAll();
        $result = [];

        foreach ($rows as $row) {
            $result[$row['key']] = $this->castValue($row['value'], $row['type'] ?? 'string');
        }

        return $result;
    }

    public function setMany(array $settings): bool
    {
        foreach ($settings as $key => $value) {
            $type = match (true) {
                is_bool($value)  => 'bool',
                is_int($value)   => 'int',
                is_float($value) => 'float',
                is_array($value) => 'json',
                default          => 'string',
            };
            $this->set($key, $value, $type);
        }

        return true;
    }

    public function getAll(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT `key`, `value`, `type` FROM `{$table}` ORDER BY `key` ASC";

        $rows   = $this->executeQuery($sql)->fetchAll();
        $result = [];

        foreach ($rows as $row) {
            $result[$row['key']] = $this->castValue($row['value'], $row['type'] ?? 'string');
        }

        // Populate the request-level cache.
        self::$cache = array_merge(self::$cache, $result);

        return $result;
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int'    => (int) $value,
            'float'  => (float) $value,
            'json'   => json_decode((string) $value, true),
            default  => $value,
        };
    }
}
