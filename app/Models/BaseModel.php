<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static bool $softDelete = false;

    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getTable(): string
    {
        return Database::getPrefix() . static::$table;
    }

    public function find(int|string $id): ?array
    {
        $table = $this->getTable();
        $pk    = static::$primaryKey;
        $sql   = "SELECT * FROM `{$table}` WHERE `{$pk}` = :id";

        if (static::$softDelete) {
            $sql .= ' AND `deleted_at` IS NULL';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->executeQuery($sql, [':id' => $id]);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findBy(string $column, mixed $value): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `{$column}` = :value";

        if (static::$softDelete) {
            $sql .= ' AND `deleted_at` IS NULL';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->executeQuery($sql, [':value' => $value]);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findAll(
        array $conditions = [],
        string $orderBy = 'id DESC',
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $table = $this->getTable();

        if (static::$softDelete) {
            $conditions['deleted_at'] = null;
        }

        [$whereSql, $bindings] = $this->buildWhere($conditions);

        $sql = "SELECT * FROM `{$table}`{$whereSql} ORDER BY {$orderBy}";

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        return $this->executeQuery($sql, $bindings)->fetchAll();
    }

    public function where(array $conditions): array
    {
        return $this->findAll($conditions);
    }

    public function create(array $data): int|string
    {
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        if (empty($data)) {
            throw new RuntimeException('No fillable data provided for insert.');
        }

        $table   = $this->getTable();
        $columns = array_keys($data);
        $placeholders = array_map(fn(string $col) => ":{$col}", $columns);

        $colList = implode(', ', array_map(fn(string $c) => "`{$c}`", $columns));
        $valList = implode(', ', $placeholders);

        $sql      = "INSERT INTO `{$table}` ({$colList}) VALUES ({$valList})";
        $bindings = array_combine($placeholders, array_values($data));

        $this->executeQuery($sql, $bindings);

        return $this->db->lastInsertId();
    }

    public function update(int|string $id, array $data): bool
    {
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        if (empty($data)) {
            return false;
        }

        $table  = $this->getTable();
        $pk     = static::$primaryKey;
        $setClauses = [];
        $bindings   = [':pk_id' => $id];

        foreach ($data as $col => $val) {
            $placeholder          = ":{$col}";
            $setClauses[]         = "`{$col}` = {$placeholder}";
            $bindings[$placeholder] = $val;
        }

        $setStr = implode(', ', $setClauses);
        $sql    = "UPDATE `{$table}` SET {$setStr} WHERE `{$pk}` = :pk_id";

        $stmt = $this->executeQuery($sql, $bindings);

        return $stmt->rowCount() > 0;
    }

    public function delete(int|string $id): bool
    {
        $table = $this->getTable();
        $pk    = static::$primaryKey;

        if (static::$softDelete) {
            $sql = "UPDATE `{$table}` SET `deleted_at` = NOW() WHERE `{$pk}` = :id AND `deleted_at` IS NULL";
        } else {
            $sql = "DELETE FROM `{$table}` WHERE `{$pk}` = :id";
        }

        $stmt = $this->executeQuery($sql, [':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function count(array $conditions = []): int
    {
        $table = $this->getTable();

        if (static::$softDelete) {
            $conditions['deleted_at'] = null;
        }

        [$whereSql, $bindings] = $this->buildWhere($conditions);

        $sql = "SELECT COUNT(*) FROM `{$table}`{$whereSql}";

        return (int) $this->executeQuery($sql, $bindings)->fetchColumn();
    }

    public function paginate(
        int $page = 1,
        int $perPage = 20,
        array $conditions = [],
        string $orderBy = 'id DESC'
    ): array {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $total   = $this->count($conditions);
        $items   = $this->findAll($conditions, $orderBy, $perPage, $offset);

        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    /**
     * Build WHERE clause from conditions array.
     * Supports exact match and IS NULL (when value is null).
     * Returns [sql_fragment, bindings].
     */
    public function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $clauses  = [];
        $bindings = [];
        $index    = 0;

        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $clauses[] = "`{$col}` IS NULL";
            } else {
                $placeholder          = ":where_{$col}_{$index}";
                $clauses[]            = "`{$col}` = {$placeholder}";
                $bindings[$placeholder] = $val;
            }
            $index++;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    public function executeQuery(string $sql, array $bindings = []): PDOStatement
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Query failed: {$e->getMessage()} | SQL: {$sql}",
                (int) $e->getCode(),
                $e
            );
        }
    }
}
